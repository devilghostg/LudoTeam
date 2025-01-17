<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/game')]
final class GameController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    #[Route('/', name: 'app_game_index', methods: ['GET'])]
    public function index(Request $request, GameRepository $gameRepository): Response
    {
        // Récupérer les filtres
        $availability = $request->query->get('availability');
        $minPlayers = $request->query->get('min_players');
        $maxPlayers = $request->query->get('max_players');
        $searchTerm = $request->query->get('search');

        // Créer la requête de base
        $qb = $gameRepository->createQueryBuilder('g');

        // Filtre par disponibilité
        if ($availability) {
            if ($availability === 'available') {
                $qb->andWhere('g.isAvailable = :available')
                   ->setParameter('available', true);
            } elseif ($availability === 'unavailable') {
                $qb->andWhere('g.isAvailable = :available')
                   ->setParameter('available', false);
            }
        }

        // Filtre par nombre de joueurs
        if ($minPlayers) {
            $qb->andWhere('g.minPlayers >= :minPlayers')
               ->setParameter('minPlayers', $minPlayers);
        }
        if ($maxPlayers) {
            $qb->andWhere('g.maxPlayers <= :maxPlayers')
               ->setParameter('maxPlayers', $maxPlayers);
        }

        // Recherche par nom ou description
        if ($searchTerm) {
            $qb->andWhere('g.name LIKE :search OR g.description LIKE :search')
               ->setParameter('search', '%'.$searchTerm.'%');
        }

        // Exécuter la requête
        $games = $qb->getQuery()->getResult();

        // Si on demande du JSON
        if ($request->headers->get('Accept') === 'application/json') {
            return $this->json($games, 200, [], ['groups' => 'game:read']);
        }

        // Sinon on renvoie du HTML
        return $this->render('game/index.html.twig', [
            'games' => $games,
        ]);
    }

    #[Route('/new', name: 'app_game_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $game = new Game();
        $game->setOwner($this->getUser());
        $game->setIsAvailable(true);

        // Si c'est une requête API
        if ($request->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($request->getContent(), true);
            
            // Désérialise les données JSON vers l'objet Game
            $this->serializer->deserialize(
                $request->getContent(),
                Game::class,
                'json',
                ['groups' => 'game:write', AbstractNormalizer::OBJECT_TO_POPULATE => $game]
            );

            $this->entityManager->persist($game);
            $this->entityManager->flush();

            return $this->json($game, 201, [], ['groups' => 'game:read']);
        }

        // Sinon on gère le formulaire HTML
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($game);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_game_index');
        }

        return $this->render('game/new.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_game_show', methods: ['GET'])]
    public function show(Request $request, Game $game): Response
    {
        if ($request->headers->get('Accept') === 'application/json') {
            return $this->json($game, 200, [], ['groups' => 'game:read']);
        }

        return $this->render('game/show.html.twig', [
            'game' => $game,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_game_edit', methods: ['GET', 'PUT', 'POST'])]
    public function edit(Request $request, Game $game): Response
    {
        if ($game->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce jeu.');
        }

        // Si c'est une requête API
        if ($request->headers->get('Content-Type') === 'application/json') {
            $this->serializer->deserialize(
                $request->getContent(),
                Game::class,
                'json',
                ['groups' => 'game:write', AbstractNormalizer::OBJECT_TO_POPULATE => $game]
            );

            $this->entityManager->flush();

            return $this->json($game, 200, [], ['groups' => 'game:read']);
        }

        // Sinon on gère le formulaire HTML
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_game_index');
        }

        return $this->render('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_game_delete', methods: ['DELETE'])]
    public function delete(Request $request, Game $game): Response
    {
        if ($game->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce jeu.');
        }

        // Pour l'API, pas besoin de token CSRF
        if ($request->headers->get('Content-Type') === 'application/json') {
            $this->entityManager->remove($game);
            $this->entityManager->flush();

            return new JsonResponse(null, 204);
        }

        // Pour le formulaire HTML, on vérifie le token CSRF
        if ($this->isCsrfTokenValid('delete'.$game->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($game);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_game_index');
    }
}