<?php

namespace App\Controller;

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
    public function index(Request $request, EventRepository $eventRepository): Response
    {
        // Si on demande du JSON
        if ($request->headers->get('Accept') === 'application/json') {
            $events = $eventRepository->findAll();
            return $this->json($events, 200, [], ['groups' => 'event:read']);
        }

        // Sinon on renvoie du HTML
        return $this->render('event/index.html.twig', [
            'events' => $eventRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $event = new Event();
        $event->setOrganizer($this->getUser());

        // Si c'est une requête API
        if ($request->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($request->getContent(), true);
            
            // Désérialise les données JSON vers l'objet Event
            $this->serializer->deserialize(
                $request->getContent(),
                Event::class,
                'json',
                ['groups' => 'event:write', AbstractNormalizer::OBJECT_TO_POPULATE => $event]
            );

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            return $this->json($event, 201, [], ['groups' => 'event:read']);
        }

        // Sinon on gère le formulaire HTML
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
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

            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['DELETE', 'POST'])]
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
            $this->addFlash('success', 'Événement supprimé avec succès !');
        }

        return $this->redirectToRoute('app_event_index');
    }

    #[Route('/{id}/join', name: 'app_event_join', methods: ['POST'])]
    public function join(Request $request, Event $event): Response
    {
        if (!$this->isCsrfTokenValid('join'.$event->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour participer à un événement.');
        }

        if (count($event->getParticipants()) >= $event->getMaxParticipants()) {
            $this->addFlash('error', 'Désolé, l\'événement est complet !');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if (!$event->getParticipants()->contains($user)) {
            $event->addParticipant($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Vous participez maintenant à cet événement !');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/leave', name: 'app_event_leave', methods: ['POST'])]
    public function leave(Request $request, Event $event): Response
    {
        if (!$this->isCsrfTokenValid('leave'.$event->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour quitter un événement.');
        }

        if ($event->getParticipants()->contains($user)) {
            $event->removeParticipant($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Vous ne participez plus à cet événement.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }
}