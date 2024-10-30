<?php

namespace App\Entity;

use App\Repository\ProductoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductoRepository::class)]
class Producto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column(length: 255)]
    private ?string $imagen = null;

    #[ORM\Column]
    private ?float $precio = null;

    /**
     * @var Collection<int, ProductoSolicitado>
     */
    #[ORM\OneToMany(targetEntity: ProductoSolicitado::class, mappedBy: 'id_producto')]
    private Collection $productoSolicitados;

    public function __construct()
    {
        $this->productoSolicitados = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(string $imagen): static
    {
        $this->imagen = $imagen;

        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    /**
     * @return Collection<int, ProductoSolicitado>
     */
    public function getProductoSolicitados(): Collection
    {
        return $this->productoSolicitados;
    }

    public function addProductoSolicitado(ProductoSolicitado $productoSolicitado): static
    {
        if (!$this->productoSolicitados->contains($productoSolicitado)) {
            $this->productoSolicitados->add($productoSolicitado);
            $productoSolicitado->setIdProducto($this);
        }

        return $this;
    }

    public function removeProductoSolicitado(ProductoSolicitado $productoSolicitado): static
    {
        if ($this->productoSolicitados->removeElement($productoSolicitado)) {
            // set the owning side to null (unless already changed)
            if ($productoSolicitado->getIdProducto() === $this) {
                $productoSolicitado->setIdProducto(null);
            }
        }

        return $this;
    }
}
