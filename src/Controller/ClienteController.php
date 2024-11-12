<?php

namespace App\Controller;

use App\Entity\Alquiler;
use App\Entity\Compra;
use App\Entity\Producto;
use App\Entity\ProductoSolicitado;
use App\Entity\Repacion;
use App\Entity\Solicitud;
use App\Entity\Ubicacion;
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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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

    //PROBAR POR IONIC O ANDROID
    #[Route('/usuario', name: 'mostrar_usuario', methods: ['GET'])]
    public function mostrarUsuario(TokenStorageInterface $tokenStorage, UsuarioRepository $usuarioRepository): JsonResponse
    {
        // Obtener el token de autenticación
        $token = $tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['status' => 'Token no encontrado'], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener el usuario autenticado a partir del token
        $usuario = $token->getUser();

        if (!$usuario instanceof Usuario) {
            return new JsonResponse(['status' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        // Devolver los datos del usuario en una respuesta JSON
        return new JsonResponse([
            'id' => $usuario->getId(),
            'nombre' => $usuario->getNombre(),
            'apellido1' => $usuario->getApellido1(),
            'apellido2' => $usuario->getApellido2(),
            'email' => $usuario->getEmail(),
            'rol' => $usuario->getRol()
        ], Response::HTTP_OK);
    }


    //FUNCIONA
    #[Route('/crear-usuario', name: 'crear_usuario', methods: ['POST'])]
    public function crearUsuario(Request $request, UsuarioRepository $usuarioRepository): JsonResponse
    {
        // Obtener los datos del cuerpo de la solicitud
        $data = json_decode($request->getContent(), true);

        // Validar que los datos sean correctos
        if (!isset($data['nombre'], $data['apellido1'], $data['apellido2'], $data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Datos incompletos o inválidos recibidos.'], 400);
        }

        $existingUsuario = $usuarioRepository->findOneBy(['email' => $data['email']]);
        if ($existingUsuario) {
            return new JsonResponse(['status' => 'El email proporcionado ya está en uso'], Response::HTTP_CONFLICT);
        }

        // Crear un nuevo objeto de usuario
        $usuario = new Usuario();
        $usuario->setNombre($data['nombre']);
        $usuario->setApellido1($data['apellido1']);
        $usuario->setApellido2($data['apellido2']);
        $usuario->setEmail($data['email']);
        $usuario->setPassword($data['password']);
        $usuario->setRoles(['ROLE_USER']); // Rol por defecto para nuevos usuarios
        $usuario->setRol('cliente'); // Rol por defecto para nuevos usuarios

        $usuarioRepository->addUser($usuario);

        // Devolver una respuesta de éxito
        return new JsonResponse(['status' => 'Usuario creado con exito'], Response::HTTP_CREATED);
    }


    //PROBAR POR IONIC O ANDROID
    #[Route('/eliminar-usuario', name: 'eliminar_usuario', methods: ['DELETE'])]
    public function eliminarUsuario(TokenStorageInterface $tokenStorage, UsuarioRepository $usuarioRepository): JsonResponse
    {
        // Obtener el token de autenticación
        $token = $tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['status' => 'Token no encontrado'], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener el usuario autenticado a partir del token
        $usuario = $token->getUser();

        if (!$usuario instanceof Usuario) {
            return new JsonResponse(['status' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        // Eliminar el usuario de la base de datos
        $usuarioRepository->deleteUser($usuario);

        // Devolver una respuesta JSON indicando el resultado de la operación
        return new JsonResponse(['status' => 'Usuario eliminado exitosamente'], Response::HTTP_OK);
    }

    //FUNCIONA
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


    /*#[Route('/solicitar-reparacion', name: 'solicitar_reparacion', methods: ['POST'])]
    public function solicitarReparacion(Request $request, EntityManagerInterface $entityManager, SolicitudRepository $solicitudRepository): JsonResponse
    {
        // Obtener y decodificar los datos de la solicitud
        $rawContent = $request->getContent();
        $data = json_decode($rawContent, true);

        // Validar que los datos sean correctos
        if (!isset($data['productoId']) || !isset($data['incidencia'])) {
            return new JsonResponse(['error' => 'Datos incompletos o inválidos recibidos.'], 400);
        }

        // Buscar el producto por ID
        $producto = $entityManager->getRepository(Producto::class)->find($data['productoId']);
        if (!$producto) {
            return new JsonResponse(['error' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        //$solicitudRepository->addProductoSolicitado();

        // Crear una nueva instancia de Repacion
        $reparacion = new Repacion();

        // Asignar los datos a la reparación
        $reparacion->setFechaInicio(new \DateTime()); // Fecha de inicio de la reparación
        $reparacion->setIncidencia($data['incidencia']); // Descripción de la incidencia

        // Persistir la reparación en la base de datos
        $entityManager->persist($reparacion);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Reparación solicitada con éxito'], Response::HTTP_OK);
    }*/

    #[Route('/solicitar-reparacion', name: 'solicitar_reparacion', methods: ['POST'])]
    public function solicitarReparacion(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Obtener y decodificar los datos de la solicitud
        $rawContent = $request->getContent();
        $data = json_decode($rawContent, true);

        // Validar que los datos sean correctos
        if (!isset($data['productoId']) || !isset($data['incidencia'])) {
            return new JsonResponse(['error' => 'Datos incompletos o inválidos recibidos.'], 400);
        }

        // Buscar el producto por ID
        $producto = $entityManager->getRepository(Producto::class)->find($data['productoId']);
        if (!$producto) {
            return new JsonResponse(['error' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Crear una nueva instancia de Repacion
        $reparacion = new Repacion();

        // Asignar los datos a la reparación
        $reparacion->setFechaInicio(new \DateTime()); // Fecha de inicio de la reparación
        $reparacion->setIncidencia($data['incidencia']); // Descripción de la incidencia

        // Persistir la reparación en la base de datos
        $entityManager->persist($reparacion);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Reparación solicitada con éxito'], Response::HTTP_OK);
    }

    //FUNCIONA
    #[Route('/finalizar-alquiler', name: 'finalizar_alquiler', methods: ['POST'])]
    public function finalizarAlquiler(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Obtener y decodificar los datos de la solicitud
        $rawContent = $request->getContent();
        $data = json_decode($rawContent, true);
        $usuarioId = $data['userId'];

        // Validar que los datos sean correctos
        if (!$data || !isset($data['userId']) || !isset($data['productoId']) || /*!isset($data['fechaInicio']) || !isset($data['fechaFin']) */ !isset($data['precioTotal'])) {
            return new JsonResponse(['error' => 'Datos incompletos o inválidos recibidos.'], 400);
        }

        // Buscar el usuario por ID
        $usuario = $entityManager->getRepository(Usuario::class)->find($usuarioId);
        if (!$usuario) {
            return new JsonResponse(['error' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Buscar el producto por ID
        $producto = $entityManager->getRepository(Producto::class)->find($data['productoId']);
        if (!$producto) {
            return new JsonResponse(['error' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
        }

        // Crear una nueva instancia de Alquiler (que hereda de Solicitud)
        $alquiler = new Alquiler();
        $alquiler->setPrecioSolicitud($data['precioTotal']); // Guardar el precio en la entidad Alquiler

        //$alquiler->setFechaInicio(new \DateTime($data['fechaInicio']));
        //$alquiler->setFechaFin(new \DateTime($data['fechaFin']));

        $fechaInicio = new \DateTime(); // Fecha actual
        $fechaFin = (clone $fechaInicio)->modify('+1 month'); // Fecha un mes después de la actual
    
        // Asignar las fechas al alquiler
        $alquiler->setFechaInicio($fechaInicio);
        $alquiler->setFechaFin($fechaFin);

        // Crear una instancia de ProductoSolicitado y asignar el producto a la solicitud de alquiler
        $productoSolicitado = new ProductoSolicitado();
        $productoSolicitado->setIdProducto($producto);
        $alquiler->addProductoSolicitado($productoSolicitado);
        $entityManager->persist($productoSolicitado);

        // Asignar el alquiler al usuario
        $alquiler->addUsuario($usuario);

        // Guardar el alquiler en la base de datos
        $entityManager->persist($alquiler);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Alquiler finalizado con éxito'], Response::HTTP_OK);
    }

    //PROBAR
    /*#[Route('/guardar-ubicacion', name: 'guardar_ubicacion', methods: ['POST'])]
    public function guardarUbicacion(Request $request, TokenStorageInterface $tokenStorage, UbicacionRepository $ubicacionRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validar los datos proporcionados
        if (empty($data['latitude']) || empty($data['longitude'])) {
            return new JsonResponse(['status' => 'Faltan parámetros'], Response::HTTP_BAD_REQUEST);
        }

        // Obtener el token de autenticación
        $token = $tokenStorage->getToken();

        if (!$token) {
            return new JsonResponse(['status' => 'Token no encontrado'], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener el usuario autenticado a partir del token
        $usuario = $token->getUser();

        if (!$usuario instanceof Usuario) {
            return new JsonResponse(['status' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        // Crear una nueva instancia de Ubicacion y establecer sus propiedades
        $ubicacion = new Ubicacion();
        $ubicacion->setLatitud($data['latitude']);
        $ubicacion->setLongitud($data['longitude']);
        $ubicacion->setIdUsuario($usuario);

        // Guardar la nueva ubicación en la base de datos
        $ubicacionRepository->addUbicacion($ubicacion);

        // Devolver una respuesta JSON indicando el resultado de la operación
        return new JsonResponse(['status' => 'Ubicación guardada correctamente'], Response::HTTP_CREATED);
    }*/

    #[Route('/guardar-ubicacion', name: 'guardar_ubicacion', methods: ['POST'])]
    public function guardarUbicacion(Request $request, UbicacionRepository $ubicacionRepository, UsuarioRepository $usuarioRepository): JsonResponse
    {
        // Decodificar los datos enviados en el cuerpo de la solicitud
        $data = json_decode($request->getContent(), true);

        // Validar que los parámetros latitude y longitude existan
        if (empty($data['latitude']) || empty($data['longitude'])) {
            return new JsonResponse(['status' => 'Faltan parámetros'], Response::HTTP_BAD_REQUEST);
        }

        // Obtener el ID del usuario desde el cuerpo de la solicitud
        $userId = $data['userId'] ?? null;

        if (!$userId) {
            return new JsonResponse(['status' => 'ID de usuario no proporcionado'], Response::HTTP_BAD_REQUEST);
        }

        // Buscar el usuario con el ID proporcionado
        $usuario = $usuarioRepository->find($userId);

        // Validar si el usuario existe
        if (!$usuario) {
            return new JsonResponse(['status' => 'Usuario no encontrado'], Response::HTTP_UNAUTHORIZED);
        }

        // Crear una nueva instancia de Ubicación y establecer sus propiedades
        $ubicacion = new Ubicacion();
        $ubicacion->setLatitud($data['latitude']);
        $ubicacion->setLongitud($data['longitude']);
        $ubicacion->setIdUsuario($usuario);

        // Guardar la nueva ubicación en la base de datos
        $ubicacionRepository->addUbicacion($ubicacion);

        // Devolver una respuesta JSON indicando que la ubicación fue guardada correctamente
        return new JsonResponse(['status' => 'Ubicación guardada correctamente'], Response::HTTP_CREATED);
    }



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
