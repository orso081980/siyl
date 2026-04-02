<?php

namespace App\Controller;

use App\GraphQL\SchemaBuilder;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GraphQLController extends AbstractController
{
    public function __construct(private readonly SchemaBuilder $schemaBuilder)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        // Support both GET (query param) and POST (JSON body or form)
        $data = [];

        if ($request->isMethod('GET')) {
            $data = [
                'query' => $request->query->get('query', ''),
                'variables' => json_decode($request->query->get('variables', 'null'), true),
                'operationName' => $request->query->get('operationName'),
            ];
        } else {
            $contentType = $request->headers->get('Content-Type', '');

            if (str_contains($contentType, 'application/json')) {
                $body = json_decode($request->getContent(), true);
                $data = $body ?? [];
            } else {
                $data = [
                    'query' => $request->request->get('query', ''),
                    'variables' => $request->request->all('variables'),
                    'operationName' => $request->request->get('operationName'),
                ];
            }
        }

        $query = $data['query'] ?? '';
        $variables = $data['variables'] ?? null;
        $operationName = $data['operationName'] ?? null;

        if (empty($query)) {
            return new JsonResponse(['errors' => [['message' => 'No query provided.']]], Response::HTTP_BAD_REQUEST);
        }

        $schema = $this->schemaBuilder->build();

        $debug = $this->getParameter('kernel.debug')
            ? DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE
            : DebugFlag::NONE;

        // Custom field resolver that handles Doctrine entities with private properties/getters
        $fieldResolver = static function ($source, $args, $context, \GraphQL\Type\Definition\ResolveInfo $info) {
            $fieldName = $info->fieldName;

            if (is_array($source) || $source instanceof \ArrayAccess) {
                return $source[$fieldName] ?? null;
            }

            if (is_object($source)) {
                // Try standard getter: getId(), getFirstName(), etc.
                $getter = 'get' . ucfirst($fieldName);
                if (method_exists($source, $getter)) {
                    return $source->$getter();
                }

                // Try exact method name: isRead(), isActive(), etc.
                // NOTE: 'is' + ucfirst(fieldName) would produce 'isIsRead' for fieldName='isRead',
                // which is wrong. Matching the exact fieldName as a method is the correct fallback.
                if (method_exists($source, $fieldName)) {
                    return $source->$fieldName();
                }

                // Fallback: only access public properties to avoid fatal errors on private ones.
                try {
                    $ref = new \ReflectionProperty($source, $fieldName);
                    if ($ref->isPublic()) {
                        return $source->$fieldName;
                    }
                } catch (\ReflectionException) {
                    // property doesn't exist — return null below
                }
            }

            return null;
        };

        $result = GraphQL::executeQuery($schema, $query, null, null, $variables, $operationName, $fieldResolver)
            ->toArray($debug);

        // GraphQL spec: always return 200; errors are embedded in the payload.
        return new JsonResponse($result);
    }
}
