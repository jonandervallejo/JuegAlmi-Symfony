<?php

namespace App\Entity;

use App\Repository\CompraRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompraRepository::class)]
class Compra extends Solicitud
{
   /* #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; */

    #[ORM\Column(length: 255)]
    private $adquisiciones = null;
    // Eliminamos el campo adquisiciones como string y lo cambiamos por una relación OneToMany con ProductoSolicitado
    /*#[ORM\OneToMany(targetEntity: ProductoSolicitado::class, mappedBy: "compra", cascade: ["persist"])]
    private Collection $adquisiciones;*/

    /* public function getId(): ?int
    {
        return $this->id;
    } */

  /*  public function getAdquisiciones(): Collection
    {
        return $this->adquisiciones;
    }

    public function setAdquisiciones(Collection $adquisiciones): static
    {
        $this->adquisiciones = $adquisiciones;
        return $this;
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
