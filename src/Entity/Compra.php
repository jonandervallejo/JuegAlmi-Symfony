<?php

namespace App\Entity;

use App\Repository\CompraRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompraRepository::class)]
class Compra extends Solicitud
{
   /* #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; */

    #[ORM\Column(length: 255)]
    private ?string $adquisiciones = null;

    /* public function getId(): ?int
    {
        return $this->id;
    } */

    public function getAdquisiciones(): ?string
    {
        return $this->adquisiciones;
    }

    public function setAdquisiciones(string $adquisiciones): static
    {
        $this->adquisiciones = $adquisiciones;

        return $this;
    }
}
