<?php

namespace App\Controller;

use App\Entity\Compra;
use App\Entity\Producto;
use App\Entity\ProductoSolicitado;
use App\Entity\Usuario;
use App\Repository\UbicacionRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class ClienteController extends AbstractController
{

    #[Route('/cliente', name: 'app_cliente', methods: ['GET'])]
    public function index(UsuarioRepository $usuarioRepository): Response
    {
        return $this->convertToJson($usuarioRepository->findAll());
    }

    #[Route('/ubicaciones', name: 'app_get_ubicaciones', methods: ['GET'])]
    public function getUbicaciones(UbicacionRepository $ubicacionRepository): JsonResponse
    {
        return $this->convertToJson($ubicacionRepository->findAll());
    }

    #[Route('/loginCliente', name: 'app_loginCliente', methods: ['PUT'])]
    public function loginCliente(Request $request, UsuarioRepository $usuarioRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['status' => 'Faltan parámetros'], Response::HTTP_BAD_REQUEST);
        }

        // Buscar el usuario por nombre de usuario
        $usuario = $usuarioRepository->findOneBy(['email' => $data['email']]);

        if (!$usuario || $usuario->getPassword() !== $data['password']) {
            return new JsonResponse(['status' => 'Credenciales inválidas'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'status' => 'Inicio de sesión exitoso',
            'user' => [
                'id' => $usuario->getId(),
                'email' => $usuario->getEmail(),
                'nombre' => $usuario->getNombre(),
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/guardarUbicacion', name: 'app_guardar_ubicacion', methods: ['POST'])]
    public function guardarUbicacion(Request $request, UsuarioRepository $usuarioRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validar que se proporcionen los parámetros necesarios
        if (empty($data['idUsuario']) || empty($data['latitud']) || empty($data['longitud'])) {
            return new JsonResponse(['status' => 'Faltan parámetros'], Response::HTTP_BAD_REQUEST);
        }

        // Obtener el usuario actualmente autenticado
        $usuario = $usuarioRepository->find($data['idUsuario']);

        if (!$usuario) {
            return new JsonResponse(['status' => 'Usuario no encontrado'], Response::HTTP_UNAUTHORIZED);
        }

        $usuarioRepository->guardarUbicacion($usuario, $data['latitud'], $data['longitud']);

        return new JsonResponse(['status' => 'Ubicación guardada correctamente'], Response::HTTP_CREATED);
    }


    #[Route('/finalizar-compra', name: 'finalizar_compra', methods: ['POST'])]
    public function finalizarCompra(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;

        // Buscar al usuario por ID
        $usuario = $entityManager->getRepository(Usuario::class)->find($userId);

        // Verificar si el usuario existe
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Crear una nueva instancia de Compra
        $compra = new Compra();
        $compra->setPrecioSolicitud($data['precioTotal']); // Precio total de la compra

        // Iterar por los productos en el carrito y procesarlos
        foreach ($data['productos'] as $productoData) {
            // Buscar el objeto Producto a partir de su ID
            $producto = $entityManager->getRepository(Producto::class)->find($productoData['id']);

            // Verificar si el producto existe en la base de datos
            if (!$producto) {
                return new JsonResponse(['error' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
            }

            // Crear una instancia de ProductoSolicitado y asignar el producto
            $productoSolicitado = new ProductoSolicitado();
            $productoSolicitado->setIdProducto($producto); // Asignar el objeto Producto

            // Asignar ProductoSolicitado a la compra
            $compra->addProductoSolicitado($productoSolicitado);
            $entityManager->persist($productoSolicitado);
        }

        // Asignar la solicitud al usuario actual
        $usuario = $entityManager->getRepository(Usuario::class)->find($userId);
        if ($usuario) {
            $compra->addUsuario($usuario);
        } else {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Persistir la compra en la base de datos
        $entityManager->persist($compra);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Compra finalizada con éxito'], Response::HTTP_OK);
    }


    private function convertToJson($data): JsonResponse
    {
        // Configuramos los encoders y normalizadores
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer()];

        // Creamos el servicio Serializer
        $serializer = new Serializer($normalizers, $encoders);

        // Normalizamos los datos
        $normalized = $serializer->normalize($data, null, [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d',
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId(); // Para evitar problemas de referencia circular
            }
        ]);

        // Convertimos los datos normalizados a JSON
        $jsonContent = $serializer->serialize($normalized, 'json');

        // Retornamos la respuesta en formato JSON
        return JsonResponse::fromJsonString($jsonContent, 200);
    }


}
