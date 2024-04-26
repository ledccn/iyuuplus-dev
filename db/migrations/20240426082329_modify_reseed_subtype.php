<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyReseedSubtype extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('cn_reseed');
        $isUpdate = false;
        if (!$table->hasColumn('subtype')) {
            $isUpdate = true;
            $table->addColumn('subtype', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'null' => false, 'signed' => false, 'comment' => '业务子类型', 'after' => 'status']);
        }

        if (!$table->hasColumn('payload')) {
            $isUpdate = true;
            $table->addColumn('payload', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR, 'null' => true, 'comment' => '有效载荷', 'after' => 'subtype']);
        }

        if ($isUpdate) {
            $table->update();
        }
    }
}
