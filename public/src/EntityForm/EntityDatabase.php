<?php

namespace EntityForm;

use Config\Config;
use ConnCrud\SqlCommand;
use Helpers\Check;
use Entity\Metadados;

abstract class EntityDatabase
{
    private $entity;
    private $indice = 10000;

    /**
     * @param string $entityName
     */
    public function __construct(string $entityName)
    {
        $this->entity = $entityName;
    }

    protected function createRelationalTable($dados)
    {
        $table = $this->entity . "_" . $dados['column'];

        $string = "CREATE TABLE IF NOT EXISTS `" . PRE . $table . "` ("
            . "`{$this->entity}_id` INT(11) NOT NULL,"
            . "`{$dados['relation']}_id` INT(11) NOT NULL"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $this->exeSql($string);

        $this->createIndexFk($table, $this->entity . "_id", $this->entity, $dados['column']);
        $this->createIndexFk($table, $dados['relation'] . "_id", $dados['relation'], $dados['column']);
    }

    protected function createIndexFk($table, $column, $tableTarget, $col = null, $key = null)
    {
        $delete = ($key === "publisher" ? "SET NULL" : "CASCADE");
        if(empty($col))
            $col = $column;

        $constraint = substr("c_{$this->entity}_{$col}_{$tableTarget}", 0, 64);

        $this->exeSql("ALTER TABLE `" . PRE . $table . "` ADD KEY `fk_" . $column . "` (`{$column}`)");
        $this->exeSql("ALTER TABLE `" . PRE . $table . "` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`{$column}`) REFERENCES `" . PRE . $tableTarget . "` (`id`) ON DELETE " . $delete . " ON UPDATE NO ACTION");
    }

    protected function prepareSqlColumn($dados)
    {
        if ($dados['type'] === "json") {
            $dados['type'] = "varchar";
            $dados['size'] = !empty($dados['size']) ? $dados['size'] : 8192;
        }

        $type = in_array($dados['type'], ["float", "real", "double"]) ? "double" : $dados['type'];
        $size = $dados['type'] === "decimal" ? "{$dados['size']},2" : $dados['size'];
        return "`{$dados['column']}` {$type} "
            . (!empty($size) ? "({$size}) " : ($dados['type'] === "varchar" ? "(254) " : ($dados['type'] === "decimal" ? "(15,2) " : " ")))
            . ($dados['default'] === false ? "NOT NULL " : "")
            . ($dados['default'] !== false && !empty($dados['default']) ? $this->prepareDefault($dados['default']) : ($dados['default'] !== false ? "DEFAULT NULL" : ""));
    }

    protected function getSelecaoUnique(array $data, string $select)
    {
        $inputType = json_decode(file_get_contents(PATH_HOME . VENDOR . "entity-form/public/entity/input_type.json"), true);
        $dic = Metadados::getDicionario($data['relation']);
        foreach ($dic as $item) {
            if ($item['column'] === $select) {
                $dicionario = array_replace_recursive($inputType['default'], $inputType['selecao']);
                $dicionario["nome"] = $select;
                $dicionario["column"] = Check::name($select) . '__' . Check::name($data['column']);
                $dicionario["relation"] = $item['relation'];
                $dicionario["key"] = "selecaoUnique";
                $this->indice++;

                return [$this->indice, $dicionario];
            }
        }

        return null;
    }

    protected function exeSql($sql)
    {
        $exe = new SqlCommand();
        $exe->exeCommand($sql);
        if ($exe->getErro()) {
            var_dump($sql);
            var_dump($exe->getErro());
        }
    }

    private function prepareDefault($default)
    {
        if ($default === 'datetime' || $default === 'date' || $default === 'time')
            return "";

        if (is_numeric($default))
            return "DEFAULT {$default}";

        return "DEFAULT '{$default}'";
    }
}
