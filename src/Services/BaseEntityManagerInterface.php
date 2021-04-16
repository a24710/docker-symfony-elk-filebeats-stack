<?php


namespace App\Services;


use App\Entity\BaseEntity;

interface BaseEntityManagerInterface
{
    public function populateElasticSearch(BaseEntity $entity);
}