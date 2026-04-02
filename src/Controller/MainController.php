<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/')]
    public function homepage()
    {
        $jobs = 100;

        return $this->render('main/homepage.html.twig', [
            'jobs' => $jobs,
        ]);
    }
}
