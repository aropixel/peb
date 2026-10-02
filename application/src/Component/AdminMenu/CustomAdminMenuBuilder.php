<?php

namespace App\Component\AdminMenu;

use Aropixel\AdminBundle\Component\Menu\Builder\AdminMenuBuilder;
use Aropixel\AdminBundle\Component\Menu\Builder\AdminMenuBuilderInterface;
use Aropixel\AdminBundle\Component\Menu\Model\Link;
use Aropixel\AdminBundle\Component\Menu\Model\Menu;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Menu par défaut du bundle (pages, utilisateurs…) précédé des sections propres au site.
 */
#[AutoconfigureTag('admin_menu_builder')]
#[AsAlias(id: AdminMenuBuilderInterface::class)]
class CustomAdminMenuBuilder implements AdminMenuBuilderInterface
{
    public function __construct(
        private readonly AdminMenuBuilder $defaultMenuBuilder,
    ) {
    }

    public function buildMenu(): array
    {
        return [
            $this->buildContentMenu(),
            ...$this->defaultMenuBuilder->buildMenu(),
        ];
    }

    private function buildContentMenu(): Menu
    {
        $menu = new Menu('content', 'Contenu');
        $menu->addItem(new Link('Popins', 'admin_popin_index'));
        $menu->addItem(new Link('Dons', 'admin_donation_index'));

        return $menu;
    }
}
