-- Dream Go Operadora Turistica - Esquema de base de datos
-- MariaDB / InnoDB (necesario para transacciones y FOR UPDATE)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================================
-- RBAC: roles, permisos, y pivote rol_permiso
-- ==========================================================
CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) NULL,
  es_sistema TINYINT(1) NOT NULL DEFAULT 0,
  permisos_actualizado_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permisos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(80) NOT NULL UNIQUE,
  modulo VARCHAR(60) NOT NULL,
  descripcion VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rol_permiso (
  rol_id INT UNSIGNED NOT NULL,
  permiso_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (rol_id, permiso_id),
  CONSTRAINT fk_rolpermiso_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_rolpermiso_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- USUARIOS ADMIN
-- ==========================================================
CREATE TABLE IF NOT EXISTS usuarios_admin (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0,
  rol_id INT UNSIGNED NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_login DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_usuarios_rol ON usuarios_admin(rol_id);

-- ==========================================================
-- INTENTOS DE LOGIN (rate limiting + auditoria del panel admin)
-- ==========================================================
CREATE TABLE IF NOT EXISTS intentos_login (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  exitoso TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_intentos_login_email ON intentos_login(email, creado_en);
CREATE INDEX idx_intentos_login_ip ON intentos_login(ip, creado_en);

-- ==========================================================
-- CATEGORIAS / DESTINOS
-- ==========================================================
CREATE TABLE IF NOT EXISTS categorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  tipo ENUM('nacional','internacional') NOT NULL,
  descripcion TEXT NULL,
  imagen_portada VARCHAR(255) NULL,
  orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_categorias_tipo ON categorias(tipo);

-- ==========================================================
-- PAQUETES
-- ==========================================================
CREATE TABLE IF NOT EXISTS paquetes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(180) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  resumen VARCHAR(300) NULL,
  descripcion_larga TEXT NULL,
  itinerario TEXT NULL,
  incluye TEXT NULL,
  no_incluye TEXT NULL,
  precio_desde DECIMAL(10,2) NOT NULL,
  moneda CHAR(3) NOT NULL DEFAULT 'MXN',
  duracion_dias SMALLINT UNSIGNED NULL,
  duracion_noches SMALLINT UNSIGNED NULL,
  imagen_portada VARCHAR(255) NULL,
  destacado TINYINT(1) NOT NULL DEFAULT 0,
  meta_title VARCHAR(180) NULL,
  meta_description VARCHAR(300) NULL,
  estado ENUM('borrador','publicado','archivado') NOT NULL DEFAULT 'borrador',
  creado_por INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_paquete_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
  CONSTRAINT fk_paquete_usuario FOREIGN KEY (creado_por) REFERENCES usuarios_admin(id) ON DELETE SET NULL,
  CONSTRAINT chk_paquete_precio CHECK (precio_desde >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_paquetes_estado ON paquetes(estado);
CREATE INDEX idx_paquetes_categoria ON paquetes(categoria_id);
CREATE INDEX idx_paquetes_destacado ON paquetes(destacado);

-- ==========================================================
-- IMAGENES DE PAQUETE (galeria)
-- ==========================================================
CREATE TABLE IF NOT EXISTS imagenes_paquete (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paquete_id INT UNSIGNED NOT NULL,
  ruta_original VARCHAR(255) NOT NULL,
  ruta_thumb VARCHAR(255) NOT NULL,
  alt_text VARCHAR(180) NULL,
  orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_img_paquete FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_imgpaquete_paquete ON imagenes_paquete(paquete_id);

-- ==========================================================
-- SALIDAS / FECHAS DISPONIBLES (con cupos)
-- ==========================================================
CREATE TABLE IF NOT EXISTS salidas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paquete_id INT UNSIGNED NOT NULL,
  fecha_salida DATE NOT NULL,
  fecha_regreso DATE NULL,
  cupo_maximo SMALLINT UNSIGNED NOT NULL,
  cupo_disponible SMALLINT UNSIGNED NOT NULL,
  precio_override DECIMAL(10,2) NULL,
  estado ENUM('abierta','cerrada','cancelada') NOT NULL DEFAULT 'abierta',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_salida_paquete FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
  CONSTRAINT chk_cupo CHECK (cupo_disponible <= cupo_maximo),
  CONSTRAINT chk_salida_precio CHECK (precio_override IS NULL OR precio_override >= 0),
  CONSTRAINT chk_salida_fechas CHECK (fecha_regreso IS NULL OR fecha_regreso >= fecha_salida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_salidas_paquete_fecha ON salidas(paquete_id, fecha_salida);
CREATE INDEX idx_salidas_estado ON salidas(estado);

-- ==========================================================
-- CLIENTES
-- ==========================================================
CREATE TABLE IF NOT EXISTS clientes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  telefono VARCHAR(30) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- CODIGOS DE DESCUENTO / OFERTAS
-- (se crea antes de reservas porque reservas la referencia)
-- ==========================================================
CREATE TABLE IF NOT EXISTS codigos_descuento (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) NOT NULL UNIQUE,
  tipo ENUM('porcentaje','monto_fijo') NOT NULL,
  valor DECIMAL(10,2) NOT NULL,
  alcance ENUM('global','paquete') NOT NULL DEFAULT 'global',
  paquete_id INT UNSIGNED NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  uso_maximo INT UNSIGNED NULL,
  usos_actuales INT UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_descuento_paquete FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE CASCADE,
  CONSTRAINT chk_descuento_valor CHECK (valor > 0 AND (tipo <> 'porcentaje' OR valor <= 100)),
  CONSTRAINT chk_descuento_fechas CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_descuento_activo_fechas ON codigos_descuento(activo, fecha_inicio, fecha_fin);

-- ==========================================================
-- RESERVAS
-- ==========================================================
CREATE TABLE IF NOT EXISTS reservas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_reserva VARCHAR(20) NOT NULL UNIQUE,
  salida_id INT UNSIGNED NOT NULL,
  cliente_id INT UNSIGNED NOT NULL,
  num_personas SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  codigo_descuento_id INT UNSIGNED NULL,
  precio_total DECIMAL(10,2) NOT NULL,
  estado ENUM('pendiente','confirmada','cancelada','expirada') NOT NULL DEFAULT 'pendiente',
  expira_en DATETIME NULL,
  confirmada_en DATETIME NULL,
  cancelada_en DATETIME NULL,
  notas_admin TEXT NULL,
  metodo_pago VARCHAR(30) NULL,
  referencia_pago VARCHAR(100) NULL,
  monto_pagado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reserva_salida FOREIGN KEY (salida_id) REFERENCES salidas(id) ON DELETE RESTRICT,
  CONSTRAINT fk_reserva_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_reserva_descuento FOREIGN KEY (codigo_descuento_id) REFERENCES codigos_descuento(id) ON DELETE SET NULL,
  CONSTRAINT chk_reserva_precio CHECK (precio_total >= 0),
  CONSTRAINT chk_reserva_monto_pagado CHECK (monto_pagado >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_reservas_estado ON reservas(estado);
CREATE INDEX idx_reservas_expira ON reservas(estado, expira_en);
CREATE INDEX idx_reservas_salida ON reservas(salida_id);

-- ==========================================================
-- COTIZACIONES (leads del formulario publico)
-- ==========================================================
CREATE TABLE IF NOT EXISTS cotizaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paquete_id INT UNSIGNED NULL,
  nombre VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NOT NULL,
  num_personas SMALLINT UNSIGNED NULL,
  fecha_tentativa DATE NULL,
  mensaje TEXT NULL,
  estado ENUM('nueva','contactada','convertida','descartada') NOT NULL DEFAULT 'nueva',
  ip_origen VARCHAR(45) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cotizacion_paquete FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_cotizaciones_estado ON cotizaciones(estado);
CREATE INDEX idx_cotizaciones_creado ON cotizaciones(creado_en);

-- ==========================================================
-- CONFIGURACION DEL SITIO (clave/valor)
-- ==========================================================
CREATE TABLE IF NOT EXISTS configuracion_sitio (
  clave VARCHAR(80) PRIMARY KEY,
  valor TEXT NULL,
  descripcion VARCHAR(255) NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- BLOQUES DE CONTENIDO EDITABLES (paginas publicas)
-- ==========================================================
CREATE TABLE IF NOT EXISTS bloques_pagina (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pagina VARCHAR(50) NOT NULL,
  clave VARCHAR(50) NOT NULL,
  orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  titulo VARCHAR(255) NULL,
  subtitulo VARCHAR(500) NULL,
  contenido LONGTEXT NULL,
  color_fondo VARCHAR(20) NULL,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_pagina_clave (pagina, clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- LOG DE CORREOS ENVIADOS (trazabilidad)
-- ==========================================================
CREATE TABLE IF NOT EXISTS log_correos_enviados (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('cotizacion_equipo','confirmacion_reserva','reserva_pendiente','recordatorio_viaje','reporte_periodico','otro') NOT NULL,
  destinatario VARCHAR(150) NOT NULL,
  asunto VARCHAR(200) NOT NULL,
  referencia_tipo VARCHAR(30) NULL,
  referencia_id INT UNSIGNED NULL,
  exitoso TINYINT(1) NOT NULL,
  error_detalle TEXT NULL,
  enviado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE INDEX idx_logcorreo_tipo ON log_correos_enviados(tipo);
CREATE INDEX idx_logcorreo_referencia ON log_correos_enviados(referencia_tipo, referencia_id);

SET FOREIGN_KEY_CHECKS = 1;
