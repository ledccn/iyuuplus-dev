<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 野马PT迁移到开放API鉴权
 */
final class MigrateYemaptToOpenApi extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change(): void
    {
        if ($this->hasTable('cn_sites')) {
            $this->execute("UPDATE `cn_sites` SET `nickname` = '野马PT', `cookie_required` = 0 WHERE `site` = 'yemapt'");
        }
    }
}
