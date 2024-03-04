<?php

namespace core\models{

    /**
     * Interface InterfaceBackModel
     * @package core\models
     * @property string $id
     */
    interface InterfaceBackModel{

        public function insert(array $pValues);

        public function getTupleById($pId);

        public function updateById($pId, array $pValues);

        public function deleteById($pId);

        public function all($pCondition);

        public function count($pCondition);

        public function getInsertId();

        public function generateInputsFromDescribe();
    }
}