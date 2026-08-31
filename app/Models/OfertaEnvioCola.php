<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class OfertaEnvioCola extends Model
{
    protected static string $table = 'ofertas_envio_cola';

    /**
     * Encola un envio por cada suscriptor confirmado que todavia no este en cola para esta
     * oferta. INSERT IGNORE por el UNIQUE(oferta_id, suscriptor_id): reintentar "enviar" la
     * misma oferta no duplica filas ni reencola a quien ya se le mando.
     */
    public static function encolarParaOferta(int $ofertaId): int
    {
        $stmt = self::db()->prepare(
            'INSERT IGNORE INTO ofertas_envio_cola (oferta_id, suscriptor_id)
             SELECT :oferta_id, id FROM suscriptores WHERE estado = "confirmado"'
        );
        $stmt->execute(['oferta_id' => $ofertaId]);

        return $stmt->rowCount();
    }

    public static function contarPendientes(int $ofertaId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM ofertas_envio_cola WHERE oferta_id = :oferta_id');
        $stmt->execute(['oferta_id' => $ofertaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Trae hasta $limite filas pendientes con los datos ya unidos que necesita el mailer.
     */
    public static function loteConDetalle(int $limite): array
    {
        $stmt = self::db()->prepare(
            'SELECT oc.id AS cola_id, s.id AS suscriptor_id, s.email, s.token,
                    o.id AS oferta_id, o.codigo, o.tipo, o.valor, o.fecha_fin
             FROM ofertas_envio_cola oc
             INNER JOIN suscriptores s ON s.id = oc.suscriptor_id
             INNER JOIN codigos_descuento o ON o.id = oc.oferta_id
             ORDER BY oc.id ASC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
