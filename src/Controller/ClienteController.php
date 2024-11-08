<?php

namespace App\Controller;

use App\Entity\Compra;
use App\Entity\Producto;
use App\Entity\ProductoSolicitado;
use App\Entity\Solicitud;
use App\Entity\Usuario;
use App\Repository\UbicacionRepository;
use App\Repository\UsuarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
    public function loginCliente(Request $request, UsuarioRepository $usuarioRepository, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['status' => 'Faltan parámetros'], Response::HTTP_BAD_REQUEST);
        }

        // Buscar el usuario por nombre de usuario
        $usuario = $usuarioRepository->findOneBy(['email' => $data['email']]);

        // Generar el token JWT
        $token = $jwtManager->create($usuario);

        if (!$usuario || $usuario->getPassword() !== $data['password']) {
            return new JsonResponse(['status' => 'Credenciales inválidas'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'status' => 'Inicio de sesión exitoso',
            'user' => [
                'token' => $token,
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

        return new JsonResponse($data);

        if (!$userId) {
            return new JsonResponse(['error' => 'El ID de usuario no está presente en la solicitud'], Response::HTTP_BAD_REQUEST);
        }

        // Buscar al usuario por ID
        $usuario = $entityManager->getRepository(Usuario::class)->find($userId);

        // Verificar si el usuario existe
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Crear una nueva instancia de Compra
        $solicitud = new Solicitud();
        $solicitud->setPrecioSolicitud($data['precioTotal']); // Precio total de la compra

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
            $solicitud->addProductoSolicitado($productoSolicitado);
            $entityManager->persist($productoSolicitado);
        }

        // Asignar la solicitud al usuario actual
        $usuario = $entityManager->getRepository(Usuario::class)->find($userId);
        if ($usuario) {
            $solicitud->addUsuario($usuario);
        } else {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Persistir la compra en la base de datos
        $entityManager->persist($solicitud);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Compra finalizada con éxito'], Response::HTTP_OK);
    }
    /*public function finalizarCompra(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;
        $precioTotal = $data['precioTotal'] ?? 0;
        $productos = $data['productos'] ?? [];

        // Verificar que el usuario existe
        $usuario = $entityManager->getRepository(Usuario::class)->find($userId);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Crear una nueva Solicitud
        $solicitud = new Solicitud();
        $solicitud->setPrecioSolicitud($precioTotal); // Asignamos el precio total
        $entityManager->persist($solicitud);

        // Asociar la solicitud al usuario
        $usuario->setIdSolicitud($solicitud); // Asociamos la solicitud al usuario
        $entityManager->persist($usuario);

        // Crear la entidad Compra
        $compra = new Compra();

        // Crear las adquisiciones (productos solicitados) como ArrayCollection
        $adquisiciones = new ArrayCollection();  // Asegúrate de usar ArrayCollection aquí

        foreach ($productos as $productoData) {
            $producto = $entityManager->getRepository(Producto::class)->find($productoData['id']);
            if (!$producto) {
                return new JsonResponse(['error' => 'Producto no encontrado: ' . $productoData['id']], JsonResponse::HTTP_NOT_FOUND);
            }

            // Crear una nueva instancia de ProductoSolicitado
            $productoSolicitado = new ProductoSolicitado();
            $productoSolicitado->setIdProducto($producto);  // Asociamos el producto
            $productoSolicitado->setIdSolicitud($solicitud);  // Asociamos la solicitud
            $entityManager->persist($productoSolicitado);

            // Añadir a la colección de adquisiciones
            $adquisiciones->add($productoSolicitado);
        }

        // Asociamos las adquisiciones (productos solicitados) con la compra
        $compra->setAdquisiciones($adquisiciones);  // Aquí pasamos la colección correctamente
        $entityManager->persist($compra);  // Guardamos la compra

        // Guardar
        $entityManager->flush();

        // Devolver una respuesta de éxito
        return new JsonResponse(['status' => 'Compra finalizada con éxito'], JsonResponse::HTTP_OK);
    }*/



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
