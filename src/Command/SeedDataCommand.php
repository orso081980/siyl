<?php

namespace App\Command;

use App\Entity\Admin;
use App\Entity\Appointment;
use App\Entity\Message;
use App\Entity\Professional;
use App\Entity\Review;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:seed-data', description: 'Seed the database with realistic test data')]
class SeedDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Seeding ProConnect database...');

        // ── 1. Admin ────────────────────────────────────────────────────────
        $io->section('Creating admin');
        $admin = new Admin();
        $admin->setFirstName('Marco');
        $admin->setLastName('Maffei');
        $admin->setEmail('marco@tech-paw.com');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin1234'));
        $this->em->persist($admin);
        $this->em->flush();
        $io->success('Admin created: marco@tech-paw.com');

        // ── 2. Professionals ────────────────────────────────────────────────
        $io->section('Creating 12 professionals');

        $profData = [
            // first, last, job, desc, langs, uname, location, verified, yearsExp, degrees, areasOfExpertise, whoIWorkWith, specialities, businessName, videoUrl
            ['Sofia',    'Marino',    'Family Lawyer',         'Expert in family and divorce law with 12+ years experience.',          ['it', 'en'], 'sofia.marino',    'Rome, Italy',      true,  12, ['LLM Family Law, Sapienza University'], ['Family Law', 'Divorce', 'Child Custody'],                  'Couples and individuals going through separation.',     ['Mediation', 'International custody'], 'Studio Legale Marino', 'https://youtube.com/watch?v=family-law-intro'],
            ['Luca',     'Ferrari',   'Physiotherapist',       'Sports and rehabilitation specialist with advanced certifications.',      ['it', 'en', 'fr'], 'luca.ferrari', 'Milan, Italy',  true,  8,  ['BSc Physiotherapy, UniMi', 'NASM Certified'],             ['Sports Rehab', 'Post-surgery recovery', 'Back pain'],  'Athletes and post-surgery patients.',                   ['Knee rehab', 'Running injuries'], 'Centro Fisioterapico Ferrari', 'https://vimeo.com/physio-intro'],
            ['Emma',     'Schmidt',   'Psychologist',          'CBT and anxiety management specialist.',                ['de', 'en'], 'emma.schmidt',    'Berlin, Germany',  true,  10, ['MSc Clinical Psychology, FU Berlin'],                     ['Anxiety', 'CBT', 'Depression', 'Work burnout'],        'Adults experiencing anxiety or burnout.',               ['CBT', 'EMDR', 'Mindfulness'], 'Praxis für Psychologie Schmidt', 'https://youtube.com/watch?v=cbt-intro'],
            ['James',    'Williams',  'Personal Trainer',      'Certified NASM coach specializing in weight loss and strength.',         ['en'], 'james.williams',      'London, UK',       false, 5,  ['NASM Certified Personal Trainer'],                        ['Weight loss', 'Strength training', 'Nutrition basics'], 'Busy professionals wanting to get in shape.',           ['Functional training', 'HIIT'], 'FitLife Personal Training', 'https://instagram.com/fitlife-intro'],
            ['Chiara',   'Romano',    'Nutritionist',          'Plant-based and sports nutrition expert.',          ['it', 'en'], 'chiara.romano',   'Turin, Italy',     true,  7,  ['MSc Nutrition Science, UniTo'],                           ['Sports nutrition', 'Plant-based diet', 'Weight management'], 'Athletes and people with dietary goals.',          ['Vegan nutrition', 'Gut health'], 'Nutrizione Naturale Romano', 'https://youtube.com/watch?v=nutrition-intro'],
            ['Ahmed',    'Hassan',    'Life Coach',            'Career transitions and goal setting coach.',       ['ar', 'en', 'fr'], 'ahmed.hassan', 'Paris, France', false, 9,  ['ICF Certified Coach', 'BA Psychology, Cairo University'], ['Career change', 'Goal setting', 'Leadership'],         'Professionals at a crossroads in their career.',        ['Expat coaching', 'Leadership coaching'], 'Ahmed Hassan Coaching', 'https://linkedin.com/in/ahmed-coaching-intro'],
            ['Marie',    'Dupont',    'Massage Therapist',     'Swedish, deep tissue and hot stone specialist.',        ['fr', 'en'], 'marie.dupont',    'Lyon, France',     true,  6,  ['Diploma in Therapeutic Massage, CFMB'],                   ['Swedish massage', 'Deep tissue', 'Hot stone'],         'Anyone seeking relaxation or physical relief.',         ['Sports massage', 'Prenatal massage'], 'Spa & Massage Dupont', 'https://youtube.com/watch?v=massage-intro'],
            ['Carlos',   'Garcia',    'Architect',             'Residential and interior design architect.',           ['es', 'en'], 'carlos.garcia',   'Barcelona, Spain', true,  15, ['MArch, ETSAB Barcelona'],                                 ['Residential design', 'Interior architecture', 'Renovation'], 'Homeowners planning renovation or new build.',    ['Sustainable design', 'Small-space solutions'], 'Garcia Arquitectura', 'https://vimeo.com/architecture-portfolio'],
            ['Yuki',     'Tanaka',    'Language Tutor',        'Japanese, English and Korean language instructor.',      ['ja', 'en', 'ko'], 'yuki.tanaka', 'Tokyo, Japan',   false, 4,  ['BA Linguistics, Waseda University'],                      ['Japanese', 'English', 'Korean', 'JLPT preparation'], 'Beginners and intermediate learners of Asian languages.', ['Business Japanese', 'JLPT prep'], 'Tanaka Language Academy', 'https://youtube.com/watch?v=language-intro'],
            ['Isabella', 'Costa',     'Financial Advisor',     'Retirement planning and investment specialist.',       ['it', 'en'], 'isabella.costa',  'Florence, Italy',  true,  11, ['MSc Finance, Bocconi', 'CFA Level III'],                  ['Retirement planning', 'Investment strategy', 'Tax planning'], 'Professionals and families planning their financial future.', ['ESG investing', 'Estate planning'], 'Costa Financial Planning', 'https://youtube.com/watch?v=finance-intro'],
            ['Marcus',   'Johnson',   'Dentist',               'General dentistry and cosmetic procedures.',            ['en', 'es'], 'marcus.johnson',  'Madrid, Spain',    true,  14, ['DDS, University of Madrid', 'Specialty in Cosmetic Dentistry'], ['General dentistry', 'Cosmetic procedures', 'Dental implants'], 'Patients seeking comprehensive dental care.', ['Teeth whitening', 'Invisalign'], 'Johnson Dental Clinic', 'https://youtube.com/watch?v=dentistry-intro'],
            ['Anna',     'Kowalski',  'Graphic Designer',      'Brand identity and digital design specialist.',         ['pl', 'en', 'de'], 'anna.kowalski', 'Warsaw, Poland',  false, 6,  ['MA Graphic Design, Academy of Fine Arts'],                 ['Brand identity', 'Logo design', 'Digital marketing'], 'Startups and businesses building their brand.', ['Print design', 'Web design'], 'Kowalski Design Studio', 'https://behance.net/anna-portfolio'],
        ];

        $professionals = [];
        foreach ($profData as [$first, $last, $job, $desc, $langs, $uname, $location, $verified, $yearsExp, $degrees, $areas, $whoWorkWith, $specialities, $businessName, $videoUrl]) {
            $p = new Professional();
            $p->setFirstName($first);
            $p->setLastName($last);
            $p->setUsername($uname);
            $p->setEmail(strtolower($first.'.'.$last.'@proconnect.dev'));
            $p->setPassword($this->hasher->hashPassword($p, 'pro1234'));
            $p->setJob($job);
            $p->setDescription($desc);
            $p->setLanguages($langs);
            $p->setLocation($location);
            $p->setVerified($verified);
            $p->setYearsOfExperience($yearsExp);
            $p->setDegrees($degrees);
            $p->setAreasOfExpertise($areas);
            $p->setWhoIWorkWith($whoWorkWith);
            $p->setSpecialities($specialities);
            $p->setBusinessName($businessName);
            $p->setVideoUrl($videoUrl);
            $p->setPhone('+39 0'.rand(300, 399).' '.rand(1000000, 9999999));
            $this->em->persist($p);
            $professionals[] = $p;
        }
        $this->em->flush();
        $io->success('12 professionals created');

        // ── 3. Services (2+ per professional) ────────────────────────────────
        $io->section('Creating services (2+ per professional)');

        $serviceData = [
            // Professional 0 (Sofia Marino - Family Lawyer)
            [['Initial Legal Consultation', 'One-hour session to assess your legal case.', '120.00', 60, ['it', 'en']],
             ['Divorce Mediation Session', 'Guided mediation for amicable separation.', '150.00', 90, ['it', 'en']]],

            // Professional 1 (Luca Ferrari - Physiotherapist)
            [['Physiotherapy Session', '45-min targeted rehabilitation session.', '75.00', 45, ['it', 'en', 'fr']],
             ['Sports Injury Assessment', 'Complete evaluation and treatment plan.', '90.00', 60, ['it', 'en']]],

            // Professional 2 (Emma Schmidt - Psychologist)
            [['Therapy Session (50 min)', 'Individual CBT therapy session.', '90.00', 50, ['de', 'en']],
             ['Anxiety Management Workshop', 'Group session for anxiety coping strategies.', '60.00', 75, ['de', 'en']]],

            // Professional 3 (James Williams - Personal Trainer)
            [['Personal Training (1h)', 'Customised workout session at your gym.', '65.00', 60, ['en']],
             ['Nutrition Consultation', 'Dietary advice and meal planning.', '45.00', 45, ['en']]],

            // Professional 4 (Chiara Romano - Nutritionist)
            [['Nutrition Consultation', 'Personalized meal plan and dietary advice.', '80.00', 60, ['it', 'en']],
             ['Sports Nutrition Planning', 'Performance-focused nutrition for athletes.', '95.00', 75, ['it', 'en']]],

            // Professional 5 (Ahmed Hassan - Life Coach)
            [['Life Coaching Session', '60-min coaching call for career/life goals.', '100.00', 60, ['ar', 'en', 'fr']],
             ['Career Transition Package', '3-session program for career change.', '250.00', 180, ['en', 'fr']]],

            // Professional 6 (Marie Dupont - Massage Therapist)
            [['Full Body Massage (60 min)', 'Relaxing full-body Swedish massage.', '85.00', 60, ['fr', 'en']],
             ['Deep Tissue Massage', 'Targeted relief for chronic muscle tension.', '95.00', 75, ['fr', 'en']]],

            // Professional 7 (Carlos Garcia - Architect)
            [['Architectural Consultation', 'Review of building plans and design brief.', '150.00', 90, ['es', 'en']],
             ['Interior Design Planning', 'Complete interior design concept.', '200.00', 120, ['es', 'en']]],

            // Professional 8 (Yuki Tanaka - Language Tutor)
            [['Language Lesson (45 min)', 'One-on-one language lesson via video call.', '50.00', 45, ['ja', 'en', 'ko']],
             ['JLPT Preparation Course', 'Intensive preparation for JLPT exam.', '80.00', 60, ['ja', 'en']]],

            // Professional 9 (Isabella Costa - Financial Advisor)
            [['Financial Planning Session', 'Review of your portfolio and savings strategy.', '130.00', 60, ['it', 'en']],
             ['Retirement Planning Consultation', 'Comprehensive retirement strategy.', '160.00', 90, ['it', 'en']]],

            // Professional 10 (Marcus Johnson - Dentist)
            [['Dental Check-up', 'Complete dental examination and cleaning.', '80.00', 45, ['en', 'es']],
             ['Teeth Whitening Treatment', 'Professional whitening procedure.', '250.00', 60, ['en', 'es']]],

            // Professional 11 (Anna Kowalski - Graphic Designer)
            [['Logo Design Package', 'Complete logo design with variations.', '300.00', 0, ['pl', 'en', 'de']],
             ['Brand Identity Design', 'Full brand identity including guidelines.', '500.00', 0, ['en', 'de']]],
        ];

        $services = [];
        foreach ($serviceData as $profIndex => $profServices) {
            foreach ($profServices as [$name, $desc, $cost, $duration, $langs]) {
                $s = new Service();
                $s->setName($name);
                $s->setDescription($desc);
                $s->setCost((float) $cost);
                $s->setDuration($duration);
                $s->setLanguages($langs);
                $s->setProfessional($professionals[$profIndex]);
                $this->em->persist($s);
                $services[] = $s;
            }
        }
        $this->em->flush();
        $io->success(count($services).' services created');

        // ── 4. Users ─────────────────────────────────────────────────────────
        $io->section('Creating 12 users');

        $userData = [
            ['Alessandro', 'Ricci',     'ale.ricci',    'ale.ricci@gmail.com',    '+39 345 1234567', ['it', 'en']],
            ['Giulia',     'Conti',     'giulia.conti', 'giulia.conti@gmail.com', '+39 347 2345678', ['it']],
            ['Thomas',     'Müller',    'tmuller',      'thomas.muller@gmail.com', '+49 170 3456789', ['de', 'en']],
            ['Sarah',      'Johnson',   'sarah.j',      'sarah.johnson@gmail.com', '+1 555 4567890',  ['en']],
            ['Martina',    'Esposito',  'martina.e',    'martina.e@gmail.com',    '+39 349 5678901', ['it', 'en']],
            ['David',      'Brown',     'dbrown',       'david.brown@gmail.com',  '+44 7700 678901', ['en', 'fr']],
            ['Laura',      'Bianchi',   'laura.b',      'laura.b@gmail.com',      '+39 346 7890123', ['it']],
            ['Noah',       'Martin',    'noah.martin',  'noah.martin@gmail.com',  '+33 6 89012345',  ['fr', 'en']],
            ['Valeria',    'Greco',     'valeria.g',    'valeria.g@gmail.com',    '+39 340 9012345', ['it', 'en']],
            ['Ethan',      'Clark',     'ethan.clark',  'ethan.clark@gmail.com',  '+1 555 0123456',  ['en']],
            ['Sophie',     'Dubois',    'sophie.d',     'sophie.dubois@gmail.com', '+33 6 1234567',   ['fr', 'en', 'de']],
            ['Miguel',     'Rodriguez', 'miguel.r',     'miguel.rodriguez@gmail.com', '+34 612 345678', ['es', 'en']],
        ];

        $users = [];
        foreach ($userData as [$first, $last, $uname, $email, $phone, $langs]) {
            $u = new User();
            $u->setFirstName($first);
            $u->setLastName($last);
            $u->setUsername($uname);
            $u->setEmail($email);
            $u->setPhone($phone);
            $u->setLanguages($langs);
            $u->setPassword($this->hasher->hashPassword($u, 'user1234'));
            $this->em->persist($u);
            $users[] = $u;
        }
        $this->em->flush();
        $io->success('12 users created');

        // ── 5. Appointments ──────────────────────────────────────────────────
        $io->section('Creating 12 appointments');

        $statuses = ['confirmed', 'confirmed', 'confirmed', 'pending', 'pending', 'completed', 'completed', 'completed', 'cancelled', 'confirmed', 'confirmed', 'pending'];
        $notes = [
            'First meeting to discuss the divorce proceedings.',
            'Follow-up session after knee surgery.',
            'Dealing with work-related anxiety.',
            'Weight loss programme check-in.',
            'Review of 3-month nutrition plan.',
            'Career transition — exploring options.',
            'Deep tissue massage after marathon training.',
            'Design consultation for home extension.',
            'Weekly Japanese language lesson.',
            'Annual portfolio review with pension planning.',
            'Dental check-up and cleaning.',
            'Brand identity design consultation.',
        ];

        $appointments = [];
        $baseDate = new \DateTime('2026-04-01 10:00:00');

        for ($i = 0; $i < 12; ++$i) {
            $apptDate = clone $baseDate;
            $apptDate->modify('+'.($i * 2).' days')->modify('+'.($i * 30).' minutes');

            $a = new Appointment();
            $a->setProfessional($professionals[$i]);
            $a->setUser($users[$i]);
            $a->setService($services[$i * 2]); // Use first service of each professional
            $a->setServiceName($services[$i * 2]->getName());
            $a->setDate($apptDate);
            $a->setStatus($statuses[$i]);
            $a->setNotes($notes[$i]);
            $this->em->persist($a);
            $appointments[] = $a;
        }
        $this->em->flush();
        $io->success('12 appointments created');

        // ── 6. Messages (inside appointments) ───────────────────────────────
        $io->section('Creating 12 messages (1 per appointment)');

        $msgPairs = [
            // [senderRole, content]
            ['user',         'Hello, I wanted to confirm our appointment. Is there anything I should prepare?'],
            ['professional', 'Hi! Please bring any X-ray reports you have from the past 6 months.'],
            ['user',         'I have been feeling much more anxious at work lately. Looking forward to our session.'],
            ['user',         'Quick question — should I work out the day before our session?'],
            ['professional', 'Hi Chiara, I just started a new training cycle. Should I adjust my macros accordingly?'],
            ['user',         'I am ready for the career planning call. I have updated my CV as you suggested.'],
            ['professional', 'Great! Please arrive 10 minutes early and drink plenty of water before the massage.'],
            ['user',         'I am attaching the sketch of the room dimensions. Let me know if you need anything else.'],
            ['professional', 'For our lesson today we will focus on keigo (formal speech). Please review chapter 4.'],
            ['user',         'I have recently changed jobs so my situation has changed. Can we adjust the plan?'],
            ['professional', 'Please come 15 minutes early for the dental check-up. We need to fill out paperwork.'],
            ['user',         'I need a modern logo for my tech startup. Do you have examples of similar work?'],
        ];

        for ($i = 0; $i < 12; ++$i) {
            [$senderRole, $content] = $msgPairs[$i];
            $m = new Message();
            $m->setContent($content);
            $m->setIsRead(false);
            $m->setAppointment($appointments[$i]);

            if ('user' === $senderRole) {
                $m->setSenderUser($users[$i]);
                $m->setSenderRole('user');
                $m->setRecipientProfessional($professionals[$i]);
                $m->setRecipientRole('professional');
            } else {
                $m->setSenderProfessional($professionals[$i]);
                $m->setSenderRole('professional');
                $m->setRecipientUser($users[$i]);
                $m->setRecipientRole('user');
            }

            $this->em->persist($m);
        }
        $this->em->flush();
        $io->success('12 messages created');

        // ── 7. Reviews (3 per professional) ───────────────────────────────
        $io->section('Creating reviews (3 per professional)');

        $reviewData = [
            // Professional 0 (Sofia Marino)
            [
                [5, 'Excellent legal advice, very professional and understanding.', $users[0]],
                [4, 'Helped me through a difficult divorce. Highly recommended.', $users[1]],
                [5, 'Clear communication and great results. Thank you!', $users[2]],
            ],
            // Professional 1 (Luca Ferrari)
            [
                [5, 'Luca helped me recover from knee surgery. Amazing results!', $users[3]],
                [5, 'Professional and knowledgeable. Highly recommend.', $users[4]],
                [4, 'Good physiotherapy sessions, feeling much better.', $users[5]],
            ],
            // Professional 2 (Emma Schmidt)
            [
                [5, 'Emma really helped me manage my anxiety. Life-changing sessions.', $users[6]],
                [5, 'Professional CBT therapy. Very effective approach.', $users[7]],
                [4, 'Good therapy sessions, helped with work stress.', $users[8]],
            ],
            // Professional 3 (James Williams)
            [
                [4, 'Good personal training sessions. Lost 5kg in 2 months.', $users[9]],
                [3, 'Decent workouts, but could be more challenging.', $users[10]],
                [4, 'Helpful nutrition advice along with training.', $users[11]],
            ],
            // Professional 4 (Chiara Romano)
            [
                [5, 'Chiara created the perfect meal plan for my goals.', $users[0]],
                [5, 'Excellent nutritionist, very knowledgeable about plant-based diets.', $users[1]],
                [4, 'Good advice for sports nutrition.', $users[2]],
            ],
            // Professional 5 (Ahmed Hassan)
            [
                [5, 'Ahmed helped me transition to a new career. Amazing coach!', $users[3]],
                [4, 'Good life coaching sessions, helped clarify my goals.', $users[4]],
                [5, 'Professional and insightful. Highly recommend.', $users[5]],
            ],
            // Professional 6 (Marie Dupont)
            [
                [5, 'Best massage I have ever had. So relaxing!', $users[6]],
                [4, 'Good deep tissue massage, relieved my back pain.', $users[7]],
                [5, 'Marie is very skilled. Will definitely return.', $users[8]],
            ],
            // Professional 7 (Carlos Garcia)
            [
                [5, 'Carlos designed our dream home. Exceeded expectations!', $users[9]],
                [4, 'Good architectural consultation, clear plans.', $users[10]],
                [5, 'Professional and creative. Great results.', $users[11]],
            ],
            // Professional 8 (Yuki Tanaka)
            [
                [4, 'Good Japanese lessons, improved my speaking skills.', $users[0]],
                [5, 'Yuki is patient and knowledgeable. Great teacher!', $users[1]],
                [3, 'Basic lessons, but helpful for beginners.', $users[2]],
            ],
            // Professional 9 (Isabella Costa)
            [
                [5, 'Isabella helped me plan for retirement. Very thorough.', $users[3]],
                [4, 'Good financial advice, clear investment strategy.', $users[4]],
                [5, 'Professional and trustworthy. Highly recommend.', $users[5]],
            ],
            // Professional 10 (Marcus Johnson)
            [
                [5, 'Marcus is an excellent dentist. Pain-free experience!', $users[6]],
                [4, 'Good dental check-up, professional service.', $users[7]],
                [5, 'Clean office and friendly staff. Recommend.', $users[8]],
            ],
            // Professional 11 (Anna Kowalski)
            [
                [5, 'Anna created an amazing logo for my business!', $users[9]],
                [4, 'Good design work, met all requirements.', $users[10]],
                [5, 'Creative and professional. Great results.', $users[11]],
            ],
        ];

        $totalReviews = 0;
        foreach ($reviewData as $profIndex => $profReviews) {
            foreach ($profReviews as [$rating, $comment, $user]) {
                $r = new \App\Entity\Review();
                $r->setProfessional($professionals[$profIndex]);
                $r->setUser($user);
                $r->setRating($rating);
                $r->setComment($comment);
                $this->em->persist($r);
                $totalReviews++;
            }
        }
        $this->em->flush();
        $io->success($totalReviews.' reviews created');

        $io->success('Database seeded successfully!');

        return Command::SUCCESS;
    }
}
// test