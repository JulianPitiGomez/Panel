-- =====================================================================
-- Tienda web PyMEs - esquema satelite, version 1  (F0: fundaciones)
-- =====================================================================
-- Se crea en la BASE DE CADA CLIENTE que use tienda, con el prefijo de la
-- base en lugar de {p}  (ej. ge_000001tienda_pedidos).
--
-- Reglas:
--  * Ninguna columna nueva en tablas que lee Fivewin (pedidos_encab, clientes,
--    articu...). Todo lo del ecommerce vive aca, atado por idpedido / codcli.
--  * tienda_pedidos.idpedido NO lleva FOREIGN KEY a pedidos_encab a proposito:
--    si Fivewin borra un pedido, la fila satelite tiene que quedar huerfana
--    para que la conciliacion la detecte y la marque para revision (ver plan).
--  * Idempotente: CREATE TABLE IF NOT EXISTS. La version aplicada se anota en
--    config_clientes_web.configuracion -> "tienda_esquema_version".
--  * El comando artisan que recorre las 250 bases se hace al ir a produccion;
--    mientras tanto este archivo se aplica a mano en bcn_base1.
--
-- Historial:
--  v1  2026-09-04  tienda_compradores, tienda_direcciones, tienda_pedidos
-- =====================================================================

-- Quien compra en la web. NO es lo mismo que clientes de gestion: se enlaza
-- por codcli recien cuando compra por primera vez (o cuando el comercio lo aprueba).
CREATE TABLE IF NOT EXISTS `{p}tienda_compradores` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`            VARCHAR(120) NOT NULL,
  `password_hash`    VARCHAR(255) NOT NULL,
  `email_validado`   TINYINT(1)   NOT NULL DEFAULT 0,
  `token_validacion` VARCHAR(64)  DEFAULT NULL,
  `token_vence`      DATETIME     DEFAULT NULL,
  `nombre`           VARCHAR(100) NOT NULL,
  `telefono`         VARCHAR(40)  DEFAULT NULL,
  `documento`        VARCHAR(20)  DEFAULT NULL,
  `estado`           VARCHAR(12)  NOT NULL DEFAULT 'aprobado',   -- pendiente | aprobado | rechazado
  `motivo_rechazo`   VARCHAR(200) DEFAULT NULL,
  `codcli`           INT          DEFAULT NULL,                  -- clientes.CODIGO
  `creado`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_ingreso`   DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_{p}tienda_compradores_email` (`email`),
  KEY `idx_{p}tienda_compradores_codcli` (`codcli`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Direcciones guardadas del comprador, para que la segunda compra sea de dos clics.
CREATE TABLE IF NOT EXISTS `{p}tienda_direcciones` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comprador_id`   INT UNSIGNED NOT NULL,
  `alias`          VARCHAR(40)  DEFAULT NULL,                    -- "Casa", "Trabajo"
  `calle`          VARCHAR(100) NOT NULL,
  `numero`         VARCHAR(10)  DEFAULT NULL,
  `piso`           VARCHAR(20)  DEFAULT NULL,
  `localidad`      VARCHAR(60)  NOT NULL,
  `provincia`      VARCHAR(40)  DEFAULT NULL,
  `cp`             VARCHAR(10)  DEFAULT NULL,
  `referencia`     VARCHAR(200) DEFAULT NULL,
  `predeterminada` TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_{p}tienda_direcciones_comprador` (`comprador_id`),
  CONSTRAINT `fk_{p}tienda_direcciones_comprador`
    FOREIGN KEY (`comprador_id`) REFERENCES `{p}tienda_compradores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- El satelite del pedido: todo lo que pedidos_encab no puede guardar sin ser modificada.
-- Una fila por pedido web; los pedidos cargados desde gestion o por vendedores no tienen fila.
CREATE TABLE IF NOT EXISTS `{p}tienda_pedidos` (
  `idpedido`            INT          NOT NULL,                   -- pedidos_encab.id (sin FK, ver arriba)
  `comprador_id`        INT UNSIGNED DEFAULT NULL,               -- tienda_compradores.id (null = compro sin cuenta)
  -- entrega
  `entrega_modo`        VARCHAR(12)  NOT NULL DEFAULT 'a_convenir', -- retiro | flota | correo | a_convenir
  `entrega_direccion`   VARCHAR(160) DEFAULT NULL,
  `entrega_localidad`   VARCHAR(60)  DEFAULT NULL,
  `entrega_cp`          VARCHAR(10)  DEFAULT NULL,
  `entrega_zona_id`     INT UNSIGNED DEFAULT NULL,               -- tienda_zonas.id (F2)
  `envio_importe`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `envio_transportista` VARCHAR(40)  DEFAULT NULL,
  `envio_seguimiento`   VARCHAR(60)  DEFAULT NULL,
  -- pago
  `pago_medio`          VARCHAR(20)  DEFAULT NULL,               -- mercadopago | transferencia | cuenta_corriente | a_convenir
  `pago_estado`         VARCHAR(12)  NOT NULL DEFAULT 'pendiente', -- pendiente | aprobado | rechazado | no_aplica
  `pago_referencia`     VARCHAR(80)  DEFAULT NULL,               -- id de pago MP, etc.
  `pago_importe_unico`  DECIMAL(10,2) DEFAULT NULL,              -- importe con centavos unicos para transferencias
  `pago_comprobante`    VARCHAR(254) DEFAULT NULL,               -- URL del comprobante subido
  -- importes informativos (el pedido en pedidos_det sigue siendo la verdad)
  `iva_incluido`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  -- comision BCN (F1)
  `comision_base`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,     -- items - descuentos - promos, sin envio
  `comision_pct`        DECIMAL(5,2)  NOT NULL DEFAULT 0.00,     -- copiado de config_clientes.comision al confirmar
  `comision_importe`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `comision_estado`     VARCHAR(12)  NOT NULL DEFAULT 'pendiente', -- pendiente | devengada | revertida | revisar
  -- estado propio del ecommerce (el de gestion sigue en pedidos_encab.estado)
  `estado_web`          VARCHAR(16)  NOT NULL DEFAULT 'recibido',
  `creado`              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idpedido`),
  KEY `idx_{p}tienda_pedidos_comprador` (`comprador_id`),
  KEY `idx_{p}tienda_pedidos_estado_web` (`estado_web`),
  KEY `idx_{p}tienda_pedidos_creado` (`creado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
