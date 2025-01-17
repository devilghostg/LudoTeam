<?php

namespace App\Controller;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/event')]
final class EventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository, GameRepository $gameRepository): Response
    {
        // 1. Récupérer les filtres depuis l'URL
        $dateFilter = $request->query->get('date_filter');
        $availability = $request->query->get('availability');
        $gameId = $request->query->get('game');

        // 2. Créer la requête de base
        $qb = $eventRepository->createQueryBuilder('e')
            ->orderBy('e.date', 'ASC');

        // 3. Ajouter le filtre de date si sélectionné
        if ($dateFilter) {
            $today = new \DateTime();
            $today->setTime(0, 0, 0);
            
            switch ($dateFilter) {
                case 'today':
                    $tomorrow = clone $today;
                    $tomorrow->modify('+1 day');
                    $qb->andWhere('e.date >= :start AND e.date < :end')
                       ->setParameter('start', $today)
                       ->setParameter('end', $tomorrow);
                    break;
                    
                case 'week':
                    $endWeek = clone $today;
                    $endWeek->modify('+1 week');
                    $qb->andWhere('e.date >= :start AND e.date < :end')
                       ->setParameter('start', $today)
                       ->setParameter('end', $endWeek);
                    break;
                    
                case 'month':
                    $endMonth = clone $today;
                    $endMonth->modify('+1 month');
                    $qb->andWhere('e.date >= :start AND e.date < :end')
                       ->setParameter('start', $today)
                       ->setParameter('end', $endMonth);
                    break;
            }
        }

        // 4. Ajouter le filtre de disponibilité si sélectionné
        if ($availability) {
            switch ($availability) {
                case 'available':
                    // Événements avec des places disponibles
                    $qb->andWhere('SIZE(e.participants) < e.maxParticipants');
                    break;
                    
                case 'full':
                    // Événements complets
                    $qb->andWhere('SIZE(e.participants) >= e.maxParticipants');
                    break;
            }
        }

        // 5. Ajouter le filtre par jeu si sélectionné
        if ($gameId) {
            $qb->join('e.games', 'g')
               ->andWhere('g.id = :gameId')
               ->setParameter('gameId', $gameId);
        }

        // 6. Exécuter la requête
        $events = $qb->getQuery()->getResult();

        // 7. Récupérer tous les jeux pour le filtre
        $games = $gameRepository->findAll();

        // 8. Rendre la vue avec les résultats
        return $this->render('event/index.html.twig', [
            'events' => $events,
            'games' => $games,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Request $request, Event $event): Response
    {
        if ($request->headers->get('Accept') === 'application/json') {
            return $this->json($event, 200, [], ['groups' => 'event:read']);
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'PUT', 'POST'])]
    public function edit(Request $request, Event $event): Response
    {
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cet événement.');
        }

        // Si c'est une requête API
        if ($request->headers->get('Content-Type') === 'application/json') {
            $this->serializer->deserialize(
                $request->getContent(),
                Event::class,
                'json',
                ['groups' => 'event:write', AbstractNormalizer::OBJECT_TO_POPULATE => $event]
            );

            $this->entityManager->flush();

            return $this->json($event, 200, [], ['groups' => 'event:read']);
        }

        // Sinon on gère le formulaire HTML
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['DELETE'])]
    public function delete(Request $request, Event $event): Response
    {
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cet événement.');
        }

        // Pour l'API, pas besoin de token CSRF
        if ($request->headers->get('Content-Type') === 'application/json') {
            $this->entityManager->remove($event);
            $this->entityManager->flush();

            return new JsonResponse(null, 204);
        }

        // Pour le formulaire HTML, on vérifie le token CSRF
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index');
    }
}