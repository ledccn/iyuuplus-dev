<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use plugin\admin\api\Menu;

/**
 * 删除用不到的菜单
 */
final class DeleteSystemMenu extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        if (!env('APP_DEBUG', false)) {
            Menu::delete('user');
            Menu::delete('plugin');
            Menu::delete('dev');
            Menu::delete('demos');
        }
    }
}
