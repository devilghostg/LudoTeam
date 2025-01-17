<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EventRepository;
use App\Repository\GameRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EventRepository $eventRepository, GameRepository $gameRepository): Response
    {
        // Récupérer les 3 prochains événements, triés par date
        $latestEvents = $eventRepository->findBy([], ['date' => 'ASC'], 3);
        
        // Récupérer les 4 derniers jeux
        $latestGames = $gameRepository->findBy([], ['id' => 'DESC'], 4);

        return $this->render('home/index.html.twig', [
            'latestEvents' => $latestEvents,
            'latestGames' => $latestGames,
        ]);
    }
}