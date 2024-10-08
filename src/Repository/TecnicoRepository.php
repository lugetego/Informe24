<?php


namespace App\Repository;

namespace App\Repository;

use App\Entity\Academico;
use App\Entity\Informe;
use App\Entity\Tecnico;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * TecnicoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 * @method Tecnico[]    findAll()
 */
class TecnicoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tecnico::class);
    }

    public function findOneByInforme($informe)
    {
        return $this->getEntityManager()
            ->createQuery(
                "SELECT i FROM App:Tecnico i
                    WHERE i.informe = :informe
                    "
            )
            ->setParameter('informe', $informe)
            ->getOneOrNullResult(); // Returns null if no result is found
    }

}
