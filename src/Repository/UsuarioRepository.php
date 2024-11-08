<?php

namespace App\Repository;

use App\Entity\Ubicacion;
use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Usuario>
 */
class UsuarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usuario::class);
    }

    public function guardarUbicacion(Usuario $usuario, float $latitud, float $longitud): Ubicacion
    {
        $entityManager = $this->getEntityManager();

        $ubicacion = new Ubicacion();
        $ubicacion->setIdUsuario($usuario->getId()); // Relacionar con el usuario
        $ubicacion->setLatitud($latitud);
        $ubicacion->setLongitud($longitud);

        // Guardar la ubicación en la base de datos
        $entityManager->persist($ubicacion);
        $entityManager->flush();

        return $ubicacion; // Retornar la ubicación guardada si es necesario
    }

    public function addUser(Usuario $usuario)
    {
        $this->getEntityManager()->persist($usuario);
        $this->getEntityManager()->flush();
    }


    //    /**
    //     * @return Usuario[] Returns an array of Usuario objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Usuario
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
