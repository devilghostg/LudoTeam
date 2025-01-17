<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\GameRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'games' => $games,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $event = new Event();
        $event->setOrganizer($this->getUser());
        
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'événement a été créé avec succès !');
            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
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
    public function deleteApi(Request $request, Event $event): Response
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

    #[Route('/{id}', name: 'app_event_delete_html', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Event $event): Response
    {
        // Vérifier si l'utilisateur actuel est l'organisateur
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet événement.');
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();

        $this->addFlash('success', 'L\'événement a été supprimé avec succès !');
        return $this->redirectToRoute('app_event_index');
    }

    #[Route('/{id}/join', name: 'app_event_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(Event $event): Response
    {
        $user = $this->getUser();

        // Vérifier si l'événement n'est pas complet
        if ($event->isFull()) {
            $this->addFlash('error', 'Désolé, cet événement est complet !');
            return $this->redirectToRoute('app_event_index');
        }

        // Vérifier si l'utilisateur n'est pas déjà inscrit
        if ($event->getParticipants()->contains($user)) {
            $this->addFlash('error', 'Vous êtes déjà inscrit à cet événement !');
            return $this->redirectToRoute('app_event_index');
        }

        // Ajouter l'utilisateur aux participants
        $event->addParticipant($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'Vous avez rejoint l\'événement avec succès !');
        return $this->redirectToRoute('app_event_index');
    }

    #[Route('/{id}/leave', name: 'app_event_leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(Event $event): Response
    {
        $user = $this->getUser();

        // Vérifier si l'utilisateur est participant
        if (!$event->getParticipants()->contains($user)) {
            $this->addFlash('error', 'Vous ne participez pas à cet événement !');
            return $this->redirectToRoute('app_event_index');
        }

        // Retirer l'utilisateur des participants
        $event->removeParticipant($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'Vous avez quitté l\'événement avec succès !');
        return $this->redirectToRoute('app_event_index');
    }
}