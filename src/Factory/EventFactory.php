<?php

namespace App\Factory;

use App\Entity\Event;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Event>
 */
final class EventFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Event::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $nomsSoirees = ['Soirée Jeux', 'Game Night', 'Tournoi', 'Après-midi ludique'];
    
        return [
            'name' => self::faker()->randomElement($nomsSoirees) . ' #' . self::faker()->numberBetween(1, 10),
            'date' => self::faker()->dateTimeBetween('now', '+1 month'),
            'maxParticipants' => self::faker()->numberBetween(4, 8),
            'organizer' => UserFactory::random(),
            'description' => self::faker()->text(200)
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Event $event): void {})
        ;
    }
}
