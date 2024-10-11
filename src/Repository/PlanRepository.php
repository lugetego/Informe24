<?php


namespace App\Repository;


use Doctrine\ORM\EntityRepository;

/**
 * PlanRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PlanRepository extends EntityRepository
{
    public function findOneByAnio($anio, $academico)
    {
        return $this->getEntityManager()
            ->createQuery(
                "SELECT i FROM App:Plan i
                    JOIN i.academico a
                    WHERE a.id = :academico
                    AND i.anio = :anio"
            )
            ->setParameter('academico', $academico)
            ->setParameter('anio', $anio)
            ->getSingleResult(); // Returns null if no result is found
    }
}