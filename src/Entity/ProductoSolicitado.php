<?php

namespace App\Entity;

use App\Repository\ProductoSolicitadoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductoSolicitadoRepository::class)]
class ProductoSolicitado
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productoSolicitados')]
    private ?Producto $id_producto = null;

    #[ORM\ManyToOne(inversedBy: 'productoSolicitados')]
    private ?Solicitud $id_solicitud = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdProducto(): ?Producto
    {
        return $this->id_producto;
    }

    public function setIdProducto(?Producto $id_producto): static
    {
        $this->id_producto = $id_producto;

        return $this;
    }

    public function getIdSolicitud(): ?Solicitud
    {
        return $this->id_solicitud;
    }

    public function setIdSolicitud(?Solicitud $id_solicitud): static
    {
        $this->id_solicitud = $id_solicitud;

        return $this;
    }
}
