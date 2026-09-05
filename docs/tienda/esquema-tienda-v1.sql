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
--  v1  2026-09-04  tienda_compradores, tienda_direcciones, tienda_pedidos, tienda_eventos,
--                  tienda_envios_config, tienda_zonas (F2 checkout y zonas),
--                  tienda_pagos_config, tienda_integraciones (F2 pagos),
--                  tienda_articu, tienda_correo_tarifas (F2 correo y peso),
--                  tienda_agenda, tienda_agenda_franjas, tienda_agenda_excepciones (F2 agenda);
--                  tienda_pedidos.entrega_fecha / entrega_franja_id / entrega_franja / agenda_cupo
--                  (F1 agrego comision_saldo a tienda_pedidos y tienda_eventos; v1 todavia no salio a produccion,
--                   por eso se edita en lugar de crear v2)
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
  `comision_estado`     VARCHAR(12)  NOT NULL DEFAULT 'pendiente', -- pendiente | devengada | revertida | revisar | no_aplica
  `comision_saldo`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,     -- lo que el comercio debe por este pedido: = importe al devengar, 0 al pagar o revertir (mismo patron que pedidos.saldo en resto)
  -- estado propio del ecommerce (el de gestion sigue en pedidos_encab.estado)
  `estado_web`          VARCHAR(16)  NOT NULL DEFAULT 'recibido',
  `creado`              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idpedido`),
  KEY `idx_{p}tienda_pedidos_comprador` (`comprador_id`),
  KEY `idx_{p}tienda_pedidos_estado_web` (`estado_web`),
  KEY `idx_{p}tienda_pedidos_creado` (`creado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Timeline del pedido web: lo que ve el comprador y la trazabilidad cuando alguien reclama.
-- Tambien es donde la conciliacion de comisiones deja anotado lo que hizo.
CREATE TABLE IF NOT EXISTS `{p}tienda_eventos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idpedido`   INT          NOT NULL,
  `estado`     VARCHAR(20)  NOT NULL,                             -- estado_web alcanzado, o el tipo de evento (comision_devengada, comision_revertida, ...)
  `detalle`    VARCHAR(255) DEFAULT NULL,
  `origen`     VARCHAR(20)  NOT NULL DEFAULT 'sistema',           -- sistema | comercio | comprador | conciliacion
  `usuario`    VARCHAR(60)  DEFAULT NULL,
  `notificado` TINYINT(1)   NOT NULL DEFAULT 0,
  `fecha`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_{p}tienda_eventos_pedido` (`idpedido`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Como entrega el comercio: una fila por comercio (se crea con valores por defecto
-- la primera vez que se guarda). Credenciales de correo NO van aca (tienda_integraciones, F2b).
CREATE TABLE IF NOT EXISTS `{p}tienda_envios_config` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `retiro_local`     TINYINT(1)   NOT NULL DEFAULT 1,
  `retiro_texto`     VARCHAR(200) DEFAULT NULL,                  -- "Lun a Vie 9 a 18, 25 de Mayo 123"
  `flota_propia`     TINYINT(1)   NOT NULL DEFAULT 0,            -- envio a domicilio con zonas propias
  `correo_activo`    TINYINT(1)   NOT NULL DEFAULT 0,            -- F2b: tarifario manual / API
  `correo_proveedor` VARCHAR(20)  DEFAULT NULL,
  `a_convenir`       TINYINT(1)   NOT NULL DEFAULT 1,            -- red de contencion: entra sin costo y se arregla por WhatsApp
  `origen_direccion` VARCHAR(160) DEFAULT NULL,
  `origen_cp`        VARCHAR(10)  DEFAULT NULL,
  `origen_lat`       DECIMAL(10,7) DEFAULT NULL,
  `origen_lng`       DECIMAL(10,7) DEFAULT NULL,
  `peso_default`     DECIMAL(8,3) DEFAULT NULL,                  -- F2b
  `bulto_default`    VARCHAR(40)  DEFAULT NULL,                  -- F2b
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zonas de la flota propia, con su tarifa. Tres formas de definirlas (tipo):
--   radio    : circulo de radio_km desde el origen; tarifa fija + opcional tarifa_por_km
--   cp       : lista de codigos postales (cp_lista, separados por coma)
--   poligono : dibujado en el mapa (poligono = JSON [[lat,lng],...])
-- Resolucion cuando una direccion cae en varias: gana la mas especifica (poligono > cp > radio menor).
CREATE TABLE IF NOT EXISTS `{p}tienda_zonas` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(60)  NOT NULL,
  `tipo`            VARCHAR(10)  NOT NULL DEFAULT 'radio',       -- radio | cp | poligono
  `poligono`        TEXT         DEFAULT NULL,
  `cp_lista`        TEXT         DEFAULT NULL,
  `radio_km`        DECIMAL(6,2) DEFAULT NULL,
  `tarifa`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tarifa_por_km`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `minimo_gratis`   DECIMAL(10,2) DEFAULT NULL,                  -- compra minima para envio bonificado (null = nunca)
  `demora_estimada` VARCHAR(60)  DEFAULT NULL,                   -- "24 a 48 hs", "en el dia"
  `activa`          TINYINT(1)   NOT NULL DEFAULT 1,
  `orden`           INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_{p}tienda_zonas_activa` (`activa`, `orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medios de pago que ofrece el comercio en el checkout (una fila por comercio).
-- Las credenciales de terceros (Mercado Pago, correo...) NO van aca: tienda_integraciones.
CREATE TABLE IF NOT EXISTS `{p}tienda_pagos_config` (
  `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transferencia_activa`    TINYINT(1)   NOT NULL DEFAULT 1,
  `transferencia_titular`   VARCHAR(100) DEFAULT NULL,
  `transferencia_banco`     VARCHAR(60)  DEFAULT NULL,
  `transferencia_cbu`       VARCHAR(30)  DEFAULT NULL,
  `transferencia_alias`     VARCHAR(40)  DEFAULT NULL,
  `transferencia_texto`     VARCHAR(200) DEFAULT NULL,           -- "Mandanos el comprobante por WhatsApp"
  `cuenta_corriente_activa` TINYINT(1)   NOT NULL DEFAULT 0,     -- solo compradores enlazados a clientes con LIMITE
  `mercadopago_activa`      TINYINT(1)   NOT NULL DEFAULT 0,     -- requiere tienda_integraciones.mercadopago
  `a_convenir_activa`       TINYINT(1)   NOT NULL DEFAULT 1,     -- "pago al recibir / lo arreglamos"
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Las cuentas del comercio en servicios de terceros: Mercado Pago, correo, WhatsApp, IA.
-- credenciales va CIFRADO (Laravel Crypt), nunca en logs. Una fila por servicio.
CREATE TABLE IF NOT EXISTS `{p}tienda_integraciones` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `servicio`         VARCHAR(20)  NOT NULL,                       -- mercadopago | correo | whatsapp | ia
  `proveedor`        VARCHAR(30)  DEFAULT NULL,                   -- andreani, correo_argentino, meta, openai...
  `credenciales`     TEXT         DEFAULT NULL,                   -- JSON cifrado
  `modelo`           VARCHAR(40)  DEFAULT NULL,
  `activa`           TINYINT(1)   NOT NULL DEFAULT 0,
  `verificada`       TINYINT(1)   NOT NULL DEFAULT 0,
  `verificada_fecha` DATETIME     DEFAULT NULL,
  `verificada_dato`  VARCHAR(120) DEFAULT NULL,                   -- ej. nombre de la cuenta MP que respondio
  `ultimo_error`     VARCHAR(255) DEFAULT NULL,
  `creado`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_{p}tienda_integraciones_servicio` (`servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lo que el articulo necesita para venderse online y que articu no tiene.
-- Sin peso y medidas no hay cotizacion de correo posible.
CREATE TABLE IF NOT EXISTS `{p}tienda_articu` (
  `codigo`             BIGINT       NOT NULL,                     -- articu.CODIGO
  `visible_web`        TINYINT(1)   NOT NULL DEFAULT 1,
  `destacado`          TINYINT(1)   NOT NULL DEFAULT 0,           -- F3
  `orden`              INT          NOT NULL DEFAULT 0,
  `descripcion_larga`  TEXT         DEFAULT NULL,                 -- F4
  `peso`               DECIMAL(8,3) DEFAULT NULL,                 -- kg
  `alto`               DECIMAL(8,2) DEFAULT NULL,                 -- cm
  `ancho`              DECIMAL(8,2) DEFAULT NULL,
  `largo`              DECIMAL(8,2) DEFAULT NULL,
  `bultos`             INT          DEFAULT NULL,
  `anticipacion_horas` INT          DEFAULT NULL,                 -- agenda: el que fabrica a pedido
  `consume_cupo`       TINYINT(1)   NOT NULL DEFAULT 1,           -- agenda
  `meta_titulo`        VARCHAR(120) DEFAULT NULL,                 -- F6
  `meta_descripcion`   VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tarifario MANUAL de correo (F2): el comercio carga destino, rango de peso y precio.
-- La cotizacion por API del transportista (carril B) se enchufa detras del mismo cotizador.
-- Destino por provincias (lista) o por rango de codigo postal. Gana la tarifa mas barata que aplique.
CREATE TABLE IF NOT EXISTS `{p}tienda_correo_tarifas` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(60)  NOT NULL,                        -- "Buenos Aires interior", "Patagonia"
  `tipo`            VARCHAR(10)  NOT NULL DEFAULT 'provincia',    -- provincia | cp
  `provincias`      TEXT         DEFAULT NULL,                    -- csv de nombres de provincia
  `cp_desde`        INT          DEFAULT NULL,
  `cp_hasta`        INT          DEFAULT NULL,
  `peso_desde`      DECIMAL(8,3) NOT NULL DEFAULT 0,              -- kg, inclusive
  `peso_hasta`      DECIMAL(8,3) DEFAULT NULL,                    -- kg, inclusive; null = sin tope
  `precio`          DECIMAL(10,2) NOT NULL DEFAULT 0,
  `demora_estimada` VARCHAR(60)  DEFAULT NULL,
  `activa`          TINYINT(1)   NOT NULL DEFAULT 1,
  `orden`           INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_{p}tienda_correo_tarifas_activa` (`activa`, `orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agenda de entregas (F2): para cuando lo quiere el comprador y cuando puede el comercio.
-- Una fila por comercio. Si no esta activa, la tienda se comporta como hoy (fecha libre).
CREATE TABLE IF NOT EXISTS `{p}tienda_agenda` (
  `id`                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `activa`                   TINYINT(1) NOT NULL DEFAULT 0,
  `anticipacion_min_horas`   INT        NOT NULL DEFAULT 0,      -- "no tomo pedidos para dentro de menos de N horas"
  `anticipacion_max_dias`    INT        NOT NULL DEFAULT 14,     -- hasta cuantos dias adelante se puede reservar
  `margen_minutos`           INT        NOT NULL DEFAULT 0,      -- minutos antes del inicio de la franja en que deja de ofrecerse
  `hora_corte`               TIME       DEFAULT NULL,            -- pedidos despues de esta hora van al dia siguiente
  `cupo_diario`              INT        DEFAULT NULL,            -- pedidos por dia (null = sin tope)
  `abierta`                  TINYINT(1) NOT NULL DEFAULT 1,      -- cierre manual de la tienda
  `recibe_cerrada`           TINYINT(1) NOT NULL DEFAULT 1,      -- cerrada: sigue tomando pedidos (con aviso) o no
  `muestra_cerrada`          TINYINT(1) NOT NULL DEFAULT 1,      -- cerrada: mostrar el aviso en la tienda
  `permite_lo_antes_posible` TINYINT(1) NOT NULL DEFAULT 1,      -- ofrecer "lo antes posible" ademas de las franjas
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Franjas por dia de la semana (0 = domingo ... 6 = sabado), con cupo y para que tipo de entrega valen.
CREATE TABLE IF NOT EXISTS `{p}tienda_agenda_franjas` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dia_semana`  TINYINT    NOT NULL,
  `hora_desde`  TIME       NOT NULL,
  `hora_hasta`  TIME       NOT NULL,
  `cupo_franja` INT        DEFAULT NULL,                         -- pedidos por franja (null = sin tope)
  `vale_envio`  TINYINT(1) NOT NULL DEFAULT 1,
  `vale_retiro` TINYINT(1) NOT NULL DEFAULT 1,
  `activa`      TINYINT(1) NOT NULL DEFAULT 1,
  `orden`       INT        NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_{p}tienda_agenda_franjas_dia` (`dia_semana`, `activa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Excepciones por fecha: feriados, vacaciones, o un dia que se abre fuera de lo habitual.
CREATE TABLE IF NOT EXISTS `{p}tienda_agenda_excepciones` (
  `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha`   DATE         NOT NULL,
  `cerrado` TINYINT(1)   NOT NULL DEFAULT 1,
  `motivo`  VARCHAR(80)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_{p}tienda_agenda_excepciones_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
