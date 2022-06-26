<?php

class residencias
{

    const NOMBRE_TABLA = "residencia";
    const ID_RESIDENCIA = "idResidencia";
    const TITULO = "Titulo";
    const AREA = "Area";
    const DETALLE = "Detalle";
    const ID_USUARIO = "idUsuario";

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;

    public static function get($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (empty($peticion[0]))
            return self::obtenerresidencias($idUsuario);
        else
            return self::obtenerresidencias($idUsuario, $peticion[0]);

    }

    public static function post($peticion)
    {
        $idUsuario = usuarios::autorizar();

        $body = file_get_contents('php://input');
        $residencia = json_decode($body);

        $idResidencia = residencias::crear($idUsuario, $residencia);

        http_response_code(201);
        return [
            "estado" => self::CODIGO_EXITO,
            "mensaje" => "Residencia creada",
            "id" => $idResidencia
        ];

    }

    public static function put($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            $body = file_get_contents('php://input');
            $residencia = json_decode($body);

            if (self::actualizar($idUsuario, $residencia, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro actualizado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "La residencia al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }
    }

    public static function delete($peticion)
    {
        $idUsuario = usuarios::autorizar();

        if (!empty($peticion[0])) {
            if (self::eliminar($idUsuario, $peticion[0]) > 0) {
                http_response_code(200);
                return [
                    "estado" => self::CODIGO_EXITO,
                    "mensaje" => "Registro eliminado correctamente"
                ];
            } else {
                throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO,
                    "El residencia al que intentas acceder no existe", 404);
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Falta id", 422);
        }

    }

    /**
     * Obtiene la colecci�n de residencias o un solo residencia indicado por el identificador
     * @param int $idUsuario identificador del usuario
     * @param null $idResidencia identificador del residencia (Opcional)
     * @return array registros de la tabla residencia
     * @throws Exception
     */
    private function obtenerresidencias($idUsuario, $idResidencia = NULL)
    {
        try {
            if (!$idResidencia) {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_USUARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idUsuario
                $sentencia->bindParam(1, $idUsuario, PDO::PARAM_INT);

            } else {
                $comando = "SELECT * FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_RESIDENCIA . "=? AND " .
                    self::ID_USUARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idResidencia e idUsuario
                $sentencia->bindParam(1, $idResidencia, PDO::PARAM_INT);
                $sentencia->bindParam(2, $idUsuario, PDO::PARAM_INT);
            }

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_EXITO,
                        "datos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    /**
     * A�ade un nueva residencia asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param mixed $residencia datos de la residencia
     * @return string identificador del la residencia
     * @throws ExcepcionApi
     */
    private function crear($idUsuario, $residencia)
    {
        if ($residencia) {
            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::TITULO . "," .
                    self::AREA . "," .
                    self::DETALLE . "," .
                    self::ID_USUARIO . ")" .
                    " VALUES(?,?,?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $Titulo);
                $sentencia->bindParam(2, $Area);
                $sentencia->bindParam(3, $Detalle);
                $sentencia->bindParam(4, $idUsuario);


                $Titulo = $residencia->Titulo;
                $Area = $residencia->Area;
                $Detalle = $residencia->Detalle;

                $sentencia->execute();

                // Retornar en el �ltimo id insertado
                return $pdo->lastInsertId();

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        } else {
            throw new ExcepcionApi(
                self::ESTADO_ERROR_PARAMETROS,
                utf8_encode("Error en existencia o sintaxis de par�metros"));
        }

    }

    /**
     * Actualiza la residencia especificado por idUsuario
     * @param int $idUsuario
     * @param object $residencia objeto con los valores nuevos de la residencia
     * @param int $idResidencia
     * @return PDOStatement
     * @throws Exception
     */
    private function actualizar($idUsuario, $residencia, $idResidencia)
    {
        try {
            // Creando consulta UPDATE
            $consulta = "UPDATE " . self::NOMBRE_TABLA .
                " SET " . self::TITULO . "=?," .
                self::AREA . "=?," .
                self::DETALLE . "=?" .
                " WHERE " . self::ID_RESIDENCIA . "=? AND " . self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($consulta);

            $sentencia->bindParam(1, $Titulo);
            $sentencia->bindParam(2, $Area);
            $sentencia->bindParam(3, $Detalle);
            $sentencia->bindParam(4, $idResidencia);
            $sentencia->bindParam(5, $idUsuario);

            $Titulo = $residencia->Titulo;
            $Area = $residencia->Area;
            $Detalle = $residencia->Detalle;

            // Ejecutar la sentencia
            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    /**
     * Elimina un residencia asociado a un usuario
     * @param int $idUsuario identificador del usuario
     * @param int $idResidencia identificador de la residencia
     * @return bool true si la eliminaci�n se pudo realizar, en caso contrario false
     * @throws Exception excepcion por errores en la base de datos
     */
    private function eliminar($idUsuario, $idResidencia)
    {
        try {
            // Sentencia DELETE
            $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_RESIDENCIA . "=? AND " .
                self::ID_USUARIO . "=?";

            // Preparar la sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $idResidencia);
            $sentencia->bindParam(2, $idUsuario);

            $sentencia->execute();

            return $sentencia->rowCount();

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}

