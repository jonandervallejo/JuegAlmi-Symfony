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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class ClienteController extends AbstractController
{

    //FUNCIONA
    #[Route('/cliente', name: 'app_cliente', methods: ['GET'])]
    public function index(UsuarioRepository $usuarioRepository): Response
    {
        return $this->convertToJson($usuarioRepository->findAll());
    }

    //NO LO UTILIZAMOS
    #[Route('/ubicaciones', name: 'app_get_ubicaciones', methods: ['GET'])]
    public function getUbicaciones(UbicacionRepository $ubicacionRepository): JsonResponse
    {
        return $this->convertToJson($ubicacionRepository->findAll());
    }

    //FUNCIONA
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

        return $this->convertToJson([
            'status' => 'Inicio de sesión exitoso',
            'user' => [
                'token' => $token,
                'id' => $usuario->getId(),
                'email' => $usuario->getEmail(),
                'nombre' => $usuario->getNombre(),
            ]
        ]);
    }

    //PROBAR
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

    //PROBAR
    #[Route('/crear-usuario', name: 'crear_usuario', methods: ['POST'])]
    public function crearUsuario(Request $request, UsuarioRepository $usuarioRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Paso 1: Validar si el token de reCAPTCHA está presente en la solicitud
        if (empty($data['gRecaptchaToken'])) {
            return new JsonResponse(['status' => 'Token de reCAPTCHA faltante'], Response::HTTP_BAD_REQUEST);
        }

        // Paso 2: Validar el token de reCAPTCHA con Google
        $recaptchaResponse = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->recaptchaSecretKey,
                'response' => $data['gRecaptchaToken']
            ]
        ]);

        $responseData = $recaptchaResponse->toArray();

        // En reCAPTCHA v2 solo comprobamos si 'success' es true
        if (!$responseData['success']) {
            return new JsonResponse(['status' => 'Token de reCAPTCHA no válido'], Response::HTTP_BAD_REQUEST);
        }

        // Paso 3: Validar los datos proporcionados
        if (empty($data['nombre']) || empty($data['apellido1']) || empty($data['apellido2']) || empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['status' => 'Faltan parámetros'], Response::HTTP_BAD_REQUEST);
        }

        // Paso 4: Verificar si el usuario ya existe
        $existingUsuario = $usuarioRepository->findOneBy(['email' => $data['email']]);
        if ($existingUsuario) {
            return new JsonResponse(['status' => 'El email proporcionado ya está en uso'], Response::HTTP_CONFLICT);
        }

        // Paso 5: Crear y guardar una nueva instancia de Usuario
        $usuario = new Usuario();
        $usuario->setNombre($data['nombre']);
        $usuario->setApellido1($data['apellido1']);
        $usuario->setApellido2($data['apellido2']);
        $usuario->setEmail($data['email']);
        $usuario->setPassword($data['password']);
        $usuario->setRoles('ROLE_USER'); // Rol por defecto para nuevos usuarios
        $usuario->setRol('cliente'); // Rol por defecto para nuevos usuarios

        $usuarioRepository->addUser($usuario);

        return new JsonResponse([
            'status' => 'Usuario creado exitosamente',
            'usuario' => [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
                'email' => $usuario->getEmail(),
                'apellido1' => $usuario->getApellido1(),
                'apellido2' => $usuario->getApellido2()
            ]
        ], Response::HTTP_CREATED);
    }

    //PROBAR
    #[Route('/finalizar-compra', name: 'finalizar_compra', methods: ['POST'])]
    public function finalizarCompra(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Obtener y decodificar los datos de la solicitud
        $rawContent = $request->getContent();
        $data = json_decode($rawContent, true);
        $usuarioId = $data['userId'];

        // Validar que los datos sean correctos
        if (!$data || !isset($data['userId']) || !isset($data['productos']) || !isset($data['precioTotal'])) {
            return new JsonResponse(['error' => 'Datos incompletos o inválidos recibidos.'], 400);
        }

        // Buscar el usuario por ID
        $usuario = $entityManager->getRepository(Usuario::class)->find($usuarioId);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Crear una nueva instancia de Compra (que hereda de Solicitud)
        $compra = new Compra();
        $compra->setPrecioSolicitud($data['precioTotal']); // Guardar el precio en la entidad Solicitud

        // Convertir el array de productos a JSON para guardarlo en 'adquisiciones'
        $productosJson = json_encode($data['productos']);
        $compra->setAdquisiciones($productosJson);

        // Asignar los productos a la compra
        foreach ($data['productos'] as $productoData) {
            $producto = $entityManager->getRepository(Producto::class)->find($productoData['id']);
            if (!$producto) {
                return new JsonResponse(['error' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
            }

            // Crear una instancia de ProductoSolicitado y asignar el producto a la compra
            $productoSolicitado = new ProductoSolicitado();
            $productoSolicitado->setIdProducto($producto);
            $compra->addProductoSolicitado($productoSolicitado);
            $entityManager->persist($productoSolicitado);
        }

        // Asignar la compra al usuario
        $compra->addUsuario($usuario);

        // Guardar la compra en la base de datos
        $entityManager->persist($compra);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Compra finalizada con éxito'], Response::HTTP_OK);
    }
   /* public function finalizarCompra(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Verificar que los datos se reciban correctamente
        $rawContent = $request->getContent();
        $data = json_decode($rawContent, true);
        $usuarioId=$data['userId'];

        // Verificar si se reciben los datos esperados
        if (!$data || !isset($data['userId']) || !isset($data['productos']) || !isset($data['precioTotal'])) {
            return new JsonResponse(['error' => 'Datos incompletos o inválidos recibidos.'], 400);
        }

        // Buscar al usuario por ID
        $usuario = $entityManager->getRepository(Usuario::class)->find($usuarioId);

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
        $usuario = $entityManager->getRepository(Usuario::class)->find($usuarioId);
        if ($usuario) {
            $solicitud->addUsuario($usuario);
        } else {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Persistir la compra en la base de datos
        $entityManager->persist($solicitud);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Compra finalizada con éxito'], Response::HTTP_OK);
    } */




    private function convertToJson($data): JsonResponse
    {
        // Configuramos los encoders y normalizadores
        $encoders = [new JsonEncoder()];
        $defaultContext = [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d',
            // Ignoramos la propiedad productoSolicitados para evitar serializar colecciones innecesarias
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['productoSolicitados'],
            // Evitamos problemas de referencia circular
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            }
        ];
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];

        // Creamos el servicio Serializer
        $serializer = new Serializer($normalizers, $encoders);

        // Normalizamos y serializamos los datos en formato JSON
        $normalized = $serializer->normalize($data);
        $jsonContent = $serializer->serialize($normalized, 'json');

        // Retornamos la respuesta en formato JSON
        return JsonResponse::fromJsonString($jsonContent, 200);
    }


}
