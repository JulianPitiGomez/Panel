#include "Fivewin.ch"
#include "xbrowse.ch"
#include "Tdolphin.ch"
#include "FGet.ch"
************************************************************************************************
** Pedidos Web (tienda online) dentro de gestion
**
** Reemplaza la pestaña "Pedidos web" de Catalogo del panel. Lista los pedidos que entraron
** por la tienda (tabla satelite ge_XXXXXXtienda_pedidos + pedidos_encab) con su entrega, su
** pago y su estado web, y desde aca el comercio hace todo lo que hacia en el panel:
**
**   - Pasar a pedidos: el pedido web entra a la tienda con estado 'W' (web, en revision) y
**     NO aparece en la ventana Pedidos hasta que el comercio lo pasa. Al pasarlo queda en
**     estado 'P' (pendiente) marcado como nuevo, igual que un pedido cargado a mano.
**   - Confirmar / rechazar el pago (transferencia, DEBIN) y ver el comprobante subido.
**   - Avanzar el estado web (recibido > en preparacion > listo > en camino/despachado >
**     entregado) con numero de seguimiento para los envios por correo.
**   - Generar el envio en MiCorreo (Correo Argentino) llamando al panel, que tiene las
**     credenciales del comercio.
**   - Pagos a cuenta de los mayoristas (transferencias que el cliente informa desde su portal).
**
** TODAVIA NO ESTA ENGANCHADO. Para ponerlo en marcha hace falta (ver README.md al lado):
**   1. Agregar el modulo al menu (menu_nuevo, modulo 'PEDWEB') con permisos A,M,E,R.
**   2. Que la tienda cree los pedidos con estado 'W' en vez de 'P' (cambio en el panel) y que
**      el panel conozca la letra 'W' en config/tienda.php (estados_gestion, comprometen_stock).
**   3. Configurar TIENDA_GESTION_TOKEN en el .env del panel y poner el mismo valor en TOKEN_GESTION
**      (abajo), junto con URL_PANEL. El endpoint ya existe (TiendaGestionController).
**   4. Los avisos al comprador los manda el panel: desde aca queda el evento con notificado = 0
**      y el comando "php artisan tienda:avisos-pendientes" (cada 5 min en el cron) lo manda.
**
** Permisos (cPermisos): A = pasar a pedidos, M = pagos / estados / envio, E = exportar,
**                       R = reporte.
************************************************************************************************
MEMVAR oApp
STATIC oWnd1, cVentana, oDlg, oBrwG, oQryBrw, cWhereBase, cPermi

// Panel web: base de la API que usa "Generar envio en MiCorreo". Ajustar al dominio real.
#define URL_PANEL      "https://panel.bcnsoft.com.ar"
#define TOKEN_GESTION  "CAMBIAR-POR-EL-TOKEN-DE-GESTION"

// Estado de gestion de un pedido web que todavia no fue pasado a pedidos
#define ESTADO_WEB_REVISION  "W"

// Estados web (tienda_pedidos.estado_web)
#define EW_RECIBIDO     "recibido"
#define EW_PREPARACION  "preparacion"
#define EW_LISTO        "listo"
#define EW_DESPACHADO   "despachado"
#define EW_EN_CAMINO    "en_camino"
#define EW_ENTREGADO    "entregado"
#define EW_CANCELADO    "cancelado"

// Modos de entrega (tienda_pedidos.entrega_modo)
#define EM_RETIRO       "retiro"
#define EM_FLOTA        "flota"
#define EM_CORREO       "correo"
#define EM_A_CONVENIR   "a_convenir"

// Estados de pago (tienda_pedidos.pago_estado)
#define PG_PENDIENTE    "pendiente"
#define PG_APROBADO     "aprobado"
#define PG_RECHAZADO    "rechazado"
#define PG_NO_APLICA    "no_aplica"

PROCEDURE PedidosWeb(cPermisos)
LOCAL oBar, hHand, cDialog
   cVentana := PROCNAME()
   IF ASCAN(oApp:aVentanas,cVentana) > 0
      hHand := ASCAN(oApp:aVentanas,cVentana)
      oApp:oWnd:Select(hHand)
      oApp:oWnd:oWndClient:aWnd[hHand]:Restore()
      RETURN
   ENDIF
   IF !TiendaHabilitada()
      MsgStop("Este comercio no tiene la tienda web habilitada"+CHR(10)+;
              "(no existe la tabla de pedidos web).","Pedidos web")
      RETURN
   ENDIF
   AADD(oApp:aVentanas,cVentana)
   cPermi  := cPermisos
   cDialog := "ABMS"

   // Por defecto: los pedidos abiertos (ni entregados ni cancelados)
   cWhereBase := " t.estado_web NOT IN ('entregado','cancelado') "
   oQryBrw := oApp:oServer:Query(SqlListado(cWhereBase))

   DEFINE WINDOW oWnd1 MDICHILD TITLE "Pedidos de la tienda web" ;
          OF oApp:oWnd NOZOOM ICON oApp:oIco FROM 05,05 TO 50,50
      DEFINE BUTTONBAR oBar SIZE 60,60 OF oWnd1 2010
      DEFINE BUTTON RESOURCE "MODI" OF oBar ;
         TOOLTIP "Ver el detalle del pedido" ;
         ACTION (Detalle(),oBrwG:Refresh()) ;
         PROMPT "Detalle" TOP WHEN(oQryBrw:RecCount()>0)
      DEFINE BUTTON RESOURCE "ALTA" OF oBar ;
         TOOLTIP "Pasar el pedido web a Pedidos (queda pendiente para armar y facturar)" ;
         ACTION (PasarAPedidos(),oBrwG:Refresh()) ;
         PROMPT "A pedidos" TOP WHEN(oQryBrw:RecCount()>0 .and. "A"$cPermisos)
      DEFINE BUTTON RESOURCE "PROV" OF oBar ;
         TOOLTIP "Confirmar el pago (transferencia / DEBIN)" ;
         ACTION (ConfirmarPago(),oBrwG:Refresh()) ;
         PROMPT "Pago OK" TOP WHEN(oQryBrw:RecCount()>0 .and. "M"$cPermisos)
      DEFINE BUTTON RESOURCE "BAJA" OF oBar ;
         TOOLTIP "Marcar el pago como rechazado" ;
         ACTION (RechazarPago(),oBrwG:Refresh()) ;
         PROMPT "Pago NO" TOP WHEN(oQryBrw:RecCount()>0 .and. "M"$cPermisos)
      DEFINE BUTTON RESOURCE "IMPR" OF oBar ;
         TOOLTIP "Ver el comprobante que subio el comprador" ;
         ACTION VerComprobante(oQryBrw:pago_comprobante) ;
         PROMPT "Comprob." TOP WHEN(oQryBrw:RecCount()>0)
      DEFINE BUTTON RESOURCE "REPORT" OF oBar ;
         TOOLTIP "Avanzar el estado web del pedido (le llega al comprador)" ;
         ACTION (CambiarEstadoWeb(),oBrwG:Refresh()) ;
         PROMPT "Estado" TOP WHEN(oQryBrw:RecCount()>0 .and. "M"$cPermisos)
      DEFINE BUTTON RESOURCE "PROV" OF oBar ;
         TOOLTIP "Generar el envio en la cuenta MiCorreo del comercio" ;
         ACTION (GenerarEnvioMiCorreo(),oBrwG:Refresh()) ;
         PROMPT "MiCorreo" TOP WHEN(oQryBrw:RecCount()>0 .and. "M"$cPermisos)
      DEFINE BUTTON RESOURCE "PROV" OF oBar ;
         TOOLTIP "Pagos a cuenta informados por los mayoristas" ;
         ACTION PagosCuenta() ;
         PROMPT "Pagos cta." TOP WHEN("M"$cPermisos)
      DEFINE BUTTON RESOURCE "FILT" OF oBar ;
         TOOLTIP "Filtrar pedidos web" ;
         ACTION Filtrar() ;
         PROMPT "Filtrar" TOP
      DEFINE BUTTON RESOURCE "EXCE" OF oBar ;
         TOOLTIP "Exportar a Excel" ;
         ACTION oBrwG:ToExcel() WHEN(oQryBrw:RecCount()>0 .AND. "E"$cPermisos) ;
         PROMPT "Exporta" TOP
      DEFINE BUTTON RESOURCE "IMPR" OF oBar ;
         TOOLTIP "Imprimir planilla" ;
         ACTION oBrwG:Report("Pedidos de la tienda web",.T.,.F.) ;
         PROMPT "Reporte" TOP WHEN(oQryBrw:RecCount()>0 .AND. "R"$cPermisos)
      DEFINE BUTTON RESOURCE "SALE" OF oBar ;
         TOOLTIP "Cerrar Ventana" ;
         ACTION oWnd1:End() ;
         PROMPT "Cerrar" TOP
   oWnd1:bGotFocus := { || oDlg:SetFocus }
   oWnd1:bResized  := { || Incrusta( oWnd1, oDlg, .t.) }
   DEFINE DIALOG oDlg RESOURCE cDialog OF oWnd1
      REDEFINE XBROWSE oBrwG DATASOURCE oQryBrw ;
         COLUMNS "situacion","idpedido","fecha","cliente","comprador_email","entrega","entrega_direccion",;
                 "envio_importe","total","pago","pago_estado_txt","estado_web_txt","comision_importe" ;
         HEADERS "Situacion","Nro Pedido","Fecha","Cliente","Email comprador","Entrega","Direccion / sucursal",;
                 "Envio","Total","Pago","Estado pago","Estado web","Comision" ;
         SIZES 90,65,90,220,160,130,220,80,90,110,100,120,80 ;
         FOOTERS ;
         ID 111 OF oDlg AUTOSORT ON DBLCLICK (Detalle(),oBrwG:Refresh())
      REDEFINE SAY oBrwG:oSeek PROMPT "" ID 113 OF oDlg
      oQryBrw:bOnChangePage := {|| oBrwG:Refresh() }
      oBrwG:aCols[1]:bStrData := {|| IF(oQryBrw:RecCount()>0,IF(oQryBrw:estado == ESTADO_WEB_REVISION,"En revision",;
                                       IF(oQryBrw:estado == "P","Pasado (pend.)",EtiquetaGestion(oQryBrw:estado))),"")}
      oBrwG:bClrStd := { || ColorFila() }
      oBrwG:bKeyDown := {|nKey,nFlags| Acelerador2(nKey,oBar,cPermisos)}
      oBrwG:cSeek := ""
      oBrwG:oSeek:SetText("")
      oBrwG:aCols[8]:nFooterType  := AGGR_SUM
      oBrwG:aCols[9]:nFooterType  := AGGR_SUM
      oBrwG:aCols[13]:nFooterType := AGGR_SUM
      oBrwG:Maketotals()
      PintaBrw(oBrwG,13) // CAMBIAR DEPENDIENDO DE CUANTAS COLUMNAS TENGA EL BROWSE
      ACTIVATE DIALOG oDlg CENTER NOWAIT ON INIT oDlg:Move(0,0) VALID(oWnd1:End())
   ACTIVATE WINDOW oWnd1 ON INIT Incrusta( oWnd1, oDlg, .T.) VALID(cerrar())
RETURN

************************************************************************************************
** Existe la tabla satelite de la tienda para este comercio
STATIC FUNCTION TiendaHabilitada()
LOCAL oQry, lOk := .f.
TRY
   oQry := oApp:oServer:Query("SHOW TABLES LIKE 'ge_"+oApp:cId+"tienda_pedidos'")
   lOk  := oQry:RecCount() > 0
   oQry:End()
CATCH
   lOk := .f.
END TRY
RETURN lOk

************************************************************************************************
** SQL del listado. Las etiquetas se calculan en SQL para poder ordenar y exportar.
STATIC FUNCTION SqlListado(cWhere)
LOCAL cP := "ge_"+oApp:cId
RETURN "SELECT t.idpedido, e.estado, e.nuevo, e.fecha, e.parafecha, e.cliente, e.codcli, e.observa, "+;
       " IFNULL(c.email,'') AS comprador_email, IFNULL(c.telefono,'') AS comprador_telefono, IFNULL(c.nombre,'') AS comprador_nombre, "+;
       " t.entrega_modo, IFNULL(t.entrega_direccion,'') AS entrega_direccion, IFNULL(t.entrega_localidad,'') AS entrega_localidad, "+;
       " IFNULL(t.entrega_cp,'') AS entrega_cp, IFNULL(t.entrega_sucursal_codigo,'') AS entrega_sucursal_codigo, "+;
       " IFNULL(DATE_FORMAT(t.entrega_fecha,'%d/%m/%Y'),'') AS entrega_fecha_txt, IFNULL(t.entrega_franja,'') AS entrega_franja, "+;
       " t.envio_importe, IFNULL(t.envio_transportista,'') AS envio_transportista, IFNULL(t.envio_seguimiento,'') AS envio_seguimiento, "+;
       " IFNULL(DATE_FORMAT(t.envio_importado,'%d/%m/%Y %H:%i'),'') AS envio_importado_txt, "+;
       " IFNULL(t.pago_medio,'') AS pago_medio, t.pago_estado, IFNULL(t.pago_referencia,'') AS pago_referencia, "+;
       " IFNULL(t.pago_importe_unico,0) AS pago_importe_unico, IFNULL(t.pago_comprobante,'') AS pago_comprobante, "+;
       " t.iva_incluido, t.descuento_promos, t.comision_importe, t.comision_estado, t.estado_web, "+;
       " DATE_FORMAT(t.creado,'%d/%m/%Y %H:%i') AS creado_txt, "+;
       " CASE t.entrega_modo WHEN 'retiro' THEN 'Retiro en el local' WHEN 'flota' THEN 'Reparto propio' "+;
       "      WHEN 'correo' THEN IF(IFNULL(t.entrega_sucursal_codigo,'')<>'','Correo (sucursal)','Correo (domicilio)') "+;
       "      ELSE 'A convenir' END AS entrega, "+;
       " CASE t.pago_medio WHEN 'mercadopago' THEN 'Mercado Pago' WHEN 'transferencia' THEN 'Transferencia' "+;
       "      WHEN 'debin' THEN 'DEBIN' WHEN 'cuenta_corriente' THEN 'Cuenta corriente' ELSE 'A convenir' END AS pago, "+;
       " CASE t.pago_estado WHEN 'pendiente' THEN 'Pendiente' WHEN 'aprobado' THEN 'Aprobado' "+;
       "      WHEN 'rechazado' THEN 'Rechazado' ELSE 'No aplica' END AS pago_estado_txt, "+;
       " CASE t.estado_web WHEN 'recibido' THEN 'Recibido' WHEN 'preparacion' THEN 'En preparacion' "+;
       "      WHEN 'listo' THEN IF(t.entrega_modo='retiro','Listo para retirar','Listo para salir') "+;
       "      WHEN 'despachado' THEN 'Despachado' WHEN 'enviado' THEN 'Despachado' WHEN 'en_camino' THEN 'En camino' "+;
       "      WHEN 'entregado' THEN IF(t.entrega_modo='retiro','Retirado','Entregado') "+;
       "      WHEN 'cancelado' THEN 'Cancelado' ELSE t.estado_web END AS estado_web_txt, "+;
       " '' AS situacion, "+;
       " ROUND(IFNULL((SELECT SUM(d.cantidad * (d.punitario - IFNULL(d.descu,0))) FROM "+cP+"pedidos_det d WHERE d.idpedido = t.idpedido),0) "+;
       "       - IFNULL(t.descuento_promos,0) + t.envio_importe, 2) AS total "+;
       " FROM "+cP+"tienda_pedidos t "+;
       " INNER JOIN "+cP+"pedidos_encab e ON e.id = t.idpedido "+;
       " LEFT JOIN "+cP+"tienda_compradores c ON c.id = t.comprador_id "+;
       " WHERE "+cWhere+;
       " ORDER BY t.idpedido DESC"

************************************************************************************************
** Color de la fila: en revision con pago pendiente (rojo), en revision listo para pasar (amarillo),
** cancelado (gris); el resto alternado como en Pedidos.
STATIC FUNCTION ColorFila()
IF oQryBrw:RecCount() == 0
   RETURN { CLR_BLACK, CLR_WHITE }
ENDIF
IF oQryBrw:estado_web == EW_CANCELADO .or. oQryBrw:estado $ "NR"
   RETURN { RGB(120,120,120), RGB(235,235,235) }
ENDIF
IF oQryBrw:estado == ESTADO_WEB_REVISION
   IF oQryBrw:pago_estado == PG_PENDIENTE .or. oQryBrw:pago_estado == PG_RECHAZADO
      RETURN { CLR_BLACK, RGB(255,50,50) }
   ENDIF
   RETURN { CLR_BLACK, RGB(255,235,140) }
ENDIF
RETURN If( oBrwG:KeyNo() % 2 == 0, { CLR_BLACK, RGB(193,221,255) }, { CLR_BLACK, RGB(221,245,255) } )

************************************************************************************************
** Acelerador de teclas: F3 detalle, F4 pasar a pedidos, F6 pago ok, F8 estado web
STATIC FUNCTION Acelerador2(nKey, oBar, cPermisos)
IF nKey = VK_F3
   oBar:aControls[1]:Click()
ENDIF
IF nKey = VK_F4 .and. "A"$cPermisos
   oBar:aControls[2]:Click()
ENDIF
IF nKey = VK_F6 .and. "M"$cPermisos
   oBar:aControls[3]:Click()
ENDIF
IF nKey = VK_F8 .and. "M"$cPermisos
   oBar:aControls[6]:Click()
ENDIF
RETURN NIL

************************************************************************************************
** Filtro: situacion, estado web, pago y texto (cliente, email o numero)
STATIC FUNCTION Filtrar()
LOCAL oFilt, oGet := ARRAY(4), oBot := ARRAY(2), acor, oFont, lRta := .f., cWhere,;
      nSitu := 1, aSitu := {"Abiertos (sin entregar)","Solo en revision (sin pasar a pedidos)","Ya pasados a pedidos","Todos"},;
      nEstW := 1, aEstW := {"Todos","Recibido","En preparacion","Listo","Despachado / en camino","Entregado","Cancelado"},;
      aEstWVal := {"","recibido","preparacion","listo","despachado","entregado","cancelado"},;
      nPago := 1, aPago := {"Todos","Pendiente","Aprobado","Rechazado","No aplica"},;
      aPagoVal := {"","pendiente","aprobado","rechazado","no_aplica"},;
      cTexto := SPACE(40)

DEFINE FONT oFont NAME "TAHOMA" SIZE 0,-11.5
DEFINE DIALOG oFilt TITLE "Filtrar pedidos web" FROM 05,15 TO 19,75 OF oWnd1 FONT oFont
   @ 07, 05 SAY "Situacion:"   OF oFilt PIXEL SIZE 50,20 RIGHT
   @ 22, 05 SAY "Estado web:"  OF oFilt PIXEL SIZE 50,20 RIGHT
   @ 37, 05 SAY "Pago:"        OF oFilt PIXEL SIZE 50,20 RIGHT
   @ 52, 05 SAY "Buscar:"      OF oFilt PIXEL SIZE 50,20 RIGHT
   @ 05, 60 COMBOBOX oGet[1] VAR nSitu ITEMS aSitu OF oFilt PIXEL SIZE 140,12
   @ 20, 60 COMBOBOX oGet[2] VAR nEstW ITEMS aEstW OF oFilt PIXEL SIZE 140,12
   @ 35, 60 COMBOBOX oGet[3] VAR nPago ITEMS aPago OF oFilt PIXEL SIZE 140,12
   @ 50, 60 GET oGet[4] VAR cTexto OF oFilt PIXEL SIZE 140,12
   @ 65, 60 SAY "Cliente, email del comprador o numero de pedido" OF oFilt PIXEL SIZE 160,10
   acor := AcepCanc(oFilt)
   @ acor[1],acor[2] BUTTON oBot[1] PROMPT "&Filtrar"  OF oFilt SIZE 30,10 ACTION ((lRta := .t.), oFilt:End()) PIXEL
   @ acor[3],acor[4] BUTTON oBot[2] PROMPT "&Cancelar" OF oFilt SIZE 30,10 ACTION ((lRta := .f.), oFilt:End()) PIXEL CANCEL
ACTIVATE DIALOG oFilt CENTER ON INIT oGet[1]:SetFocus()
IF !lRta
   RETURN nil
ENDIF

DO CASE
   CASE nSitu == 1 ; cWhere := " t.estado_web NOT IN ('entregado','cancelado') "
   CASE nSitu == 2 ; cWhere := " e.estado = '"+ESTADO_WEB_REVISION+"' "
   CASE nSitu == 3 ; cWhere := " e.estado <> '"+ESTADO_WEB_REVISION+"' "
   OTHERWISE       ; cWhere := " 1=1 "
ENDCASE
IF nEstW > 1
   IF aEstWVal[nEstW] == "despachado"
      cWhere += " AND t.estado_web IN ('despachado','enviado','en_camino') "
   ELSE
      cWhere += " AND t.estado_web = "+ClipValue2Sql(aEstWVal[nEstW])+" "
   ENDIF
ENDIF
IF nPago > 1
   cWhere += " AND t.pago_estado = "+ClipValue2Sql(aPagoVal[nPago])+" "
ENDIF
cTexto := ALLTRIM(cTexto)
IF !EMPTY(cTexto)
   cWhere += " AND (e.cliente LIKE "+ClipValue2Sql("%"+cTexto+"%")+" OR c.email LIKE "+ClipValue2Sql("%"+cTexto+"%")+;
             IF(VAL(cTexto) > 0," OR t.idpedido = "+ClipValue2Sql(VAL(cTexto)),"")+") "
ENDIF
cWhereBase := cWhere
Recargar()
RETURN nil

************************************************************************************************
** Vuelve a correr el listado con el filtro vigente (mismo patron que Pedidos: SetNewFilter sobre
** el WHERE), manteniendo el pedido posicionado si sigue en la lista
STATIC FUNCTION Recargar(nId)
IF nId == NIL .and. oQryBrw:RecCount() > 0
   nId := oQryBrw:idpedido
ENDIF
oQryBrw:SetNewFilter(SET_WHERE, cWhereBase, .t.)
oQryBrw:Refresh()
IF nId != NIL
   oQryBrw:GoTop()
   DO WHILE !oQryBrw:EOF()
      IF oQryBrw:idpedido == nId
         EXIT
      ENDIF
      oQryBrw:Skip()
   ENDDO
   IF oQryBrw:EOF()
      oQryBrw:GoTop()
   ENDIF
ENDIF
oBrwG:Maketotals()
oBrwG:Refresh()
RETURN nil

************************************************************************************************
** Detalle del pedido: datos, articulos, historial y botones de accion
STATIC FUNCTION Detalle()
LOCAL oForm, oFont, oFont1, oQryDet, oQryEve, oBrwDet, oBrwEve, oBot := ARRAY(6), nId,;
      cEntrega, cPago, cCabecera, cEstado, cSeguimiento
IF oQryBrw:RecCount() == 0
   RETURN nil
ENDIF
nId := oQryBrw:idpedido
oQryDet := oApp:oServer:Query("SELECT codart, detart, cantidad, punitario, IFNULL(descu,0) AS descu, "+;
                              " ROUND(cantidad * (punitario - IFNULL(descu,0)),2) AS importe "+;
                              " FROM ge_"+oApp:cId+"pedidos_det WHERE idpedido = "+ClipValue2Sql(nId))
oQryEve := oApp:oServer:Query("SELECT DATE_FORMAT(fecha,'%d/%m/%Y %H:%i') AS fecha_txt, estado, IFNULL(detalle,'') AS detalle, "+;
                              " IFNULL(origen,'') AS origen, IFNULL(usuario,'') AS usuario "+;
                              " FROM ge_"+oApp:cId+"tienda_eventos WHERE idpedido = "+ClipValue2Sql(nId)+" ORDER BY fecha, id")

cCabecera := "Pedido web N° "+ALLTRIM(STR(nId))+"  ·  "+ALLTRIM(oQryBrw:cliente)+;
             IF(!EMPTY(oQryBrw:comprador_email),"  ·  "+ALLTRIM(oQryBrw:comprador_email),"")+;
             IF(!EMPTY(oQryBrw:comprador_telefono),"  ·  Tel. "+ALLTRIM(oQryBrw:comprador_telefono),"")
cEntrega  := ALLTRIM(oQryBrw:entrega)+IF(!EMPTY(oQryBrw:entrega_direccion),": "+ALLTRIM(oQryBrw:entrega_direccion),"")+;
             IF(!EMPTY(oQryBrw:entrega_localidad),", "+ALLTRIM(oQryBrw:entrega_localidad),"")+;
             IF(!EMPTY(oQryBrw:entrega_cp)," ("+ALLTRIM(oQryBrw:entrega_cp)+")","")+;
             IF(!EMPTY(oQryBrw:entrega_fecha_txt),"  ·  para el "+oQryBrw:entrega_fecha_txt+" "+ALLTRIM(oQryBrw:entrega_franja),"")+;
             IF(oQryBrw:envio_importe > 0,"  ·  envio $ "+ALLTRIM(TRANSFORM(oQryBrw:envio_importe,"@E 999,999,999.99")),"")
cPago     := ALLTRIM(oQryBrw:pago)+"  ·  "+ALLTRIM(oQryBrw:pago_estado_txt)+;
             IF(oQryBrw:pago_medio == "transferencia" .and. oQryBrw:pago_importe_unico > 0,;
                "  ·  importe a transferir $ "+ALLTRIM(TRANSFORM(oQryBrw:pago_importe_unico,"@E 999,999,999.99")),"")+;
             IF(!EMPTY(oQryBrw:pago_referencia),"  ·  ref. "+ALLTRIM(oQryBrw:pago_referencia),"")+;
             IF(!EMPTY(oQryBrw:pago_comprobante),"  ·  con comprobante","")
cEstado   := "Estado web: "+ALLTRIM(oQryBrw:estado_web_txt)+"  ·  Gestion: "+;
             IF(oQryBrw:estado == ESTADO_WEB_REVISION,"en revision (sin pasar a pedidos)",EtiquetaGestion(oQryBrw:estado))+;
             "  ·  Comision $ "+ALLTRIM(TRANSFORM(oQryBrw:comision_importe,"@E 999,999,999.99"))+" ("+ALLTRIM(oQryBrw:comision_estado)+")"
cSeguimiento := IF(!EMPTY(oQryBrw:envio_seguimiento),"Seguimiento: "+ALLTRIM(oQryBrw:envio_seguimiento)+;
                   IF(!EMPTY(oQryBrw:envio_transportista)," · "+ALLTRIM(oQryBrw:envio_transportista),""),"")+;
                IF(!EMPTY(oQryBrw:envio_importado_txt),"  ·  Envio importado a MiCorreo el "+oQryBrw:envio_importado_txt,"")

DEFINE FONT oFont  NAME "TAHOMA" SIZE 0,-11.5
DEFINE FONT oFont1 NAME "Segoe UI Light" SIZE 0,-20
DEFINE DIALOG oForm TITLE "Detalle del pedido web" FROM 03,10 TO 33,140 OF oWnd1 FONT oFont
   @ 05, 05 SAY cCabecera OF oForm SIZE 500,14 PIXEL FONT oFont1
   @ 25, 05 SAY "Entrega:  "+cEntrega  OF oForm SIZE 500,10 PIXEL
   @ 36, 05 SAY "Pago:  "+cPago        OF oForm SIZE 500,10 PIXEL
   @ 47, 05 SAY cEstado                OF oForm SIZE 500,10 PIXEL
   @ 58, 05 SAY cSeguimiento           OF oForm SIZE 500,10 PIXEL
   IF !EMPTY(oQryBrw:observa)
      @ 69, 05 SAY "Observaciones:  "+ALLTRIM(oQryBrw:observa) OF oForm SIZE 500,10 PIXEL
   ENDIF

   @ 82, 05 SAY "ARTICULOS" OF oForm SIZE 200,12 PIXEL
   @ 94, 05 XBROWSE oBrwDet SIZE 300,110 PIXEL OF oForm DATASOURCE oQryDet ;
      COLUMNS "codart","detart","cantidad","punitario","descu","importe" ;
      HEADERS "Codigo","Detalle","Cant.","Unitario","Desc.","Importe" ;
      SIZES 60,300,50,70,50,80 FOOTERS CELL LINES NOBORDER AUTOSORT
   oBrwDet:aCols[3]:nFooterType := AGGR_SUM
   oBrwDet:aCols[6]:nFooterType := AGGR_SUM
   oBrwDet:MakeTotals()
   oBrwDet:CreateFromCode()
   PintaBrw(oBrwDet,0)

   @ 82,315 SAY "HISTORIAL" OF oForm SIZE 200,12 PIXEL
   @ 94,315 XBROWSE oBrwEve SIZE 200,110 PIXEL OF oForm DATASOURCE oQryEve ;
      COLUMNS "fecha_txt","estado","detalle","usuario" ;
      HEADERS "Fecha","Evento","Detalle","Usuario" ;
      SIZES 90,90,250,90 CELL LINES NOBORDER
   oBrwEve:CreateFromCode()
   PintaBrw(oBrwEve,0)

   @ 212, 05 BUTTON oBot[1] PROMPT "&Pasar a pedidos"  OF oForm SIZE 55,11 PIXEL ;
       ACTION (PasarAPedidos(), oForm:End()) WHEN("A"$cPermi .and. oQryBrw:estado == ESTADO_WEB_REVISION)
   @ 212, 65 BUTTON oBot[2] PROMPT "Pago &OK"          OF oForm SIZE 40,11 PIXEL ;
       ACTION (ConfirmarPago(), oForm:End()) WHEN("M"$cPermi .and. oQryBrw:pago_estado == PG_PENDIENTE)
   @ 212,110 BUTTON oBot[3] PROMPT "Pago &rechazado"   OF oForm SIZE 50,11 PIXEL ;
       ACTION (RechazarPago(), oForm:End()) WHEN("M"$cPermi .and. oQryBrw:pago_estado == PG_PENDIENTE)
   @ 212,165 BUTTON oBot[4] PROMPT "&Comprobante"      OF oForm SIZE 45,11 PIXEL ;
       ACTION VerComprobante(oQryBrw:pago_comprobante) WHEN(!EMPTY(oQryBrw:pago_comprobante))
   @ 212,215 BUTTON oBot[5] PROMPT "&Estado web"       OF oForm SIZE 45,11 PIXEL ;
       ACTION (CambiarEstadoWeb(), oForm:End()) WHEN("M"$cPermi)
   @ 212,265 BUTTON oBot[6] PROMPT "&MiCorreo"         OF oForm SIZE 40,11 PIXEL ;
       ACTION (GenerarEnvioMiCorreo(), oForm:End()) ;
       WHEN("M"$cPermi .and. oQryBrw:entrega_modo == EM_CORREO .and. oQryBrw:envio_transportista == "correo_argentino" .and. EMPTY(oQryBrw:envio_importado_txt))
   @ 212,470 BUTTON PROMPT "Cerrar" OF oForm SIZE 40,11 PIXEL ACTION oForm:End() CANCEL
ACTIVATE DIALOG oForm CENTER
oQryDet:End()
oQryEve:End()
RETURN nil

************************************************************************************************
** Pasar a pedidos: el pedido web deja de estar "en revision" y entra a la ventana Pedidos
** como pendiente y nuevo, igual que uno cargado a mano.
STATIC FUNCTION PasarAPedidos()
LOCAL nId, oError
IF oQryBrw:RecCount() == 0
   RETURN nil
ENDIF
nId := oQryBrw:idpedido
IF oQryBrw:estado != ESTADO_WEB_REVISION
   MsgInfo("El pedido N° "+ALLTRIM(STR(nId))+" ya fue pasado a pedidos (estado de gestion: "+;
           EtiquetaGestion(oQryBrw:estado)+").","Pedidos web")
   RETURN nil
ENDIF
IF oQryBrw:estado_web == EW_CANCELADO
   MsgStop("El pedido esta cancelado; no corresponde pasarlo a pedidos.","Pedidos web")
   RETURN nil
ENDIF
IF oQryBrw:pago_estado == PG_PENDIENTE .or. oQryBrw:pago_estado == PG_RECHAZADO
   IF !MsgNoYes("El pago de este pedido esta "+LOWER(ALLTRIM(oQryBrw:pago_estado_txt))+"."+CHR(10)+CHR(10)+;
                "¿Pasarlo a pedidos igual?","Pago sin confirmar")
      RETURN nil
   ENDIF
ENDIF
IF !MsgYesNo("Pasar el pedido web N° "+ALLTRIM(STR(nId))+" de "+ALLTRIM(oQryBrw:cliente)+CHR(10)+;
             "a Pedidos, para armarlo y facturarlo.","Pasar a pedidos")
   RETURN nil
ENDIF
TRY
   oApp:oServer:BeginTransaction()
   oApp:oServer:Execute("UPDATE ge_"+oApp:cId+"pedidos_encab SET estado = 'P', nuevo = 1 WHERE id = "+ClipValue2Sql(nId)+;
                        " AND estado = '"+ESTADO_WEB_REVISION+"'")
   RegistrarEvento(nId, "pasado_gestion", "Pasado a Pedidos de gestion")
   oApp:oServer:CommitTransaction()
   Auditar(1," Pedido web N° "+ALLTRIM(STR(nId))+" pasado a pedidos ("+ALLTRIM(oQryBrw:cliente)+")")
   Recargar(nId)
CATCH oError
   oApp:oServer:RollBack()
   ValidaError(oError)
END TRY
RETURN nil

************************************************************************************************
** Pago confirmado por el comercio (transferencia o DEBIN que vio acreditado)
STATIC FUNCTION ConfirmarPago()
LOCAL nId, oError
IF oQryBrw:RecCount() == 0
   RETURN nil
ENDIF
nId := oQryBrw:idpedido
IF oQryBrw:pago_estado != PG_PENDIENTE
   MsgInfo("El pago de este pedido no esta pendiente (esta "+LOWER(ALLTRIM(oQryBrw:pago_estado_txt))+").","Pedidos web")
   RETURN nil
ENDIF
IF !MsgYesNo("Confirmar el pago del pedido N° "+ALLTRIM(STR(nId))+CHR(10)+;
             ALLTRIM(oQryBrw:pago)+IF(oQryBrw:pago_importe_unico > 0," por $ "+ALLTRIM(TRANSFORM(oQryBrw:pago_importe_unico,"@E 999,999,999.99")),"")+;
             CHR(10)+CHR(10)+"Hacelo solo si ya viste el dinero acreditado.","Confirmar pago")
   RETURN nil
ENDIF
TRY
   oApp:oServer:BeginTransaction()
   oApp:oServer:Execute("UPDATE ge_"+oApp:cId+"tienda_pedidos SET pago_estado = '"+PG_APROBADO+"' WHERE idpedido = "+ClipValue2Sql(nId))
   RegistrarEvento(nId, "pago_aprobado", "Pago confirmado por el comercio")
   oApp:oServer:CommitTransaction()
   Auditar(1," Pago confirmado pedido web N° "+ALLTRIM(STR(nId)))
   Recargar(nId)
CATCH oError
   oApp:oServer:RollBack()
   ValidaError(oError)
END TRY
RETURN nil

************************************************************************************************
** Pago rechazado (no llego, importe distinto, comprobante falso)
STATIC FUNCTION RechazarPago()
LOCAL nId, oError, cMotivo
IF oQryBrw:RecCount() == 0
   RETURN nil
ENDIF
nId := oQryBrw:idpedido
IF oQryBrw:pago_estado != PG_PENDIENTE
   MsgInfo("El pago de este pedido no esta pendiente (esta "+LOWER(ALLTRIM(oQryBrw:pago_estado_txt))+").","Pedidos web")
   RETURN nil
ENDIF
cMotivo := PedirMotivo("Rechazar pago","Motivo (opcional, queda en el historial del pedido):")
IF cMotivo == NIL
   RETURN nil
ENDIF
IF !MsgNoYes("Marcar el pago del pedido N° "+ALLTRIM(STR(nId))+" como RECHAZADO?","Rechazar pago")
   RETURN nil
ENDIF
TRY
   oApp:oServer:BeginTransaction()
   oApp:oServer:Execute("UPDATE ge_"+oApp:cId+"tienda_pedidos SET pago_estado = '"+PG_RECHAZADO+"' WHERE idpedido = "+ClipValue2Sql(nId))
   RegistrarEvento(nId, "pago_rechazado", "Pago rechazado por el comercio"+IF(EMPTY(cMotivo),"",": "+cMotivo))
   oApp:oServer:CommitTransaction()
   Auditar(1," Pago rechazado pedido web N° "+ALLTRIM(STR(nId)))
   Recargar(nId)
CATCH oError
   oApp:oServer:RollBack()
   ValidaError(oError)
END TRY
RETURN nil

************************************************************************************************
** Abre en el navegador el comprobante que subio el comprador (es una URL publica del bucket)
STATIC FUNCTION VerComprobante(cUrl)
IF EMPTY(cUrl)
   MsgInfo("Este pedido no tiene comprobante subido.","Pedidos web")
   RETURN nil
ENDIF
ShellExecute( oWnd1:hWnd, "open", ALLTRIM(cUrl) )
RETURN nil

************************************************************************************************
** Avanzar (o deshacer) el estado web. Mismas reglas que el panel:
**   retiro     : recibido > preparacion > listo > entregado
**   flota      : recibido > preparacion > listo > en_camino > entregado
**   correo     : recibido > preparacion > listo > despachado > entregado (despachado pide seguimiento)
**   a convenir : recibido > preparacion > listo > (en_camino | despachado | entregado) > entregado
STATIC FUNCTION CambiarEstadoWeb()
LOCAL nId, oError, cActual, cModo, aSig, cAnt, aOpc := {}, aVal := {}, nOpc := 1, cNuevo,;
      oForm, oFont, oBot := ARRAY(2), lRta := .f., cSeg, oGet := ARRAY(2), lAvance, acor, cDetalle
IF oQryBrw:RecCount() == 0
   RETURN nil
ENDIF
nId     := oQryBrw:idpedido
cModo   := ALLTRIM(oQryBrw:entrega_modo)
cActual := NormalizarEstadoWeb(oQryBrw:estado_web)
aSig    := SiguientesWeb(cActual, cModo)
cAnt    := AnteriorWeb(cActual, cModo)
IF EMPTY(aSig) .and. cAnt == NIL
   MsgInfo("El pedido esta "+LOWER(EtiquetaWeb(cActual,cModo))+": no hay cambios de estado posibles.","Estado web")
   RETURN nil
ENDIF
AEVAL(aSig, {|c| AADD(aOpc, EtiquetaWeb(c,cModo)), AADD(aVal, c)})
IF cAnt != NIL
   AADD(aOpc, "Volver a: "+EtiquetaWeb(cAnt,cModo))
   AADD(aVal, cAnt)
ENDIF
cSeg := PADR(ALLTRIM(oQryBrw:envio_seguimiento), 60)

DEFINE FONT oFont NAME "TAHOMA" SIZE 0,-11.5
DEFINE DIALOG oForm TITLE "Estado web del pedido N° "+ALLTRIM(STR(nId)) FROM 05,15 TO 17,80 OF oWnd1 FONT oFont
   @ 05, 05 SAY "Estado actual: "+EtiquetaWeb(cActual,cModo)+"  ("+ALLTRIM(oQryBrw:entrega)+")" OF oForm PIXEL SIZE 195,10
   @ 20, 05 SAY "Pasar a:" OF oForm PIXEL SIZE 50,10 RIGHT
   @ 18, 60 COMBOBOX oGet[1] VAR nOpc ITEMS aOpc OF oForm PIXEL SIZE 130,12
   @ 35, 05 SAY "Seguimiento:" OF oForm PIXEL SIZE 50,10 RIGHT
   @ 33, 60 GET oGet[2] VAR cSeg OF oForm PIXEL SIZE 130,12 PICTURE "@!"
   @ 48, 60 SAY "Numero de seguimiento del correo (obligatorio al despachar por correo)" OF oForm PIXEL SIZE 195,10
   acor := AcepCanc(oForm)
   @ acor[1],acor[2] BUTTON oBot[1] PROMPT "&Aceptar"  OF oForm SIZE 30,10 ACTION ((lRta := .t.), oForm:End()) PIXEL
   @ acor[3],acor[4] BUTTON oBot[2] PROMPT "&Cancelar" OF oForm SIZE 30,10 ACTION ((lRta := .f.), oForm:End()) PIXEL CANCEL
ACTIVATE DIALOG oForm CENTER
IF !lRta
   RETURN nil
ENDIF
cNuevo := aVal[nOpc]
cSeg   := ALLTRIM(cSeg)
IF cNuevo == EW_DESPACHADO .and. cModo == EM_CORREO .and. EMPTY(cSeg)
   MsgStop("Para marcar despachado por correo carga el numero de seguimiento.","Estado web")
   RETURN nil
ENDIF
lAvance  := ASCAN(aSig, cNuevo) > 0
cDetalle := EtiquetaWeb(cNuevo,cModo)+IF(cNuevo == EW_DESPACHADO .and. !EMPTY(cSeg),". Numero de seguimiento: "+cSeg,"")
TRY
   oApp:oServer:BeginTransaction()
   oApp:oServer:Execute("UPDATE ge_"+oApp:cId+"tienda_pedidos SET estado_web = "+ClipValue2Sql(cNuevo)+;
                        IF(EMPTY(cSeg),"",", envio_seguimiento = "+ClipValue2Sql(cSeg))+;
                        " WHERE idpedido = "+ClipValue2Sql(nId))
   // notificado = 0: el aviso al comprador (mail / WhatsApp) lo manda el panel, no gestion.
   // Solo los avances avisan; volver atras no (seria confundir al comprador).
   RegistrarEvento(nId, cNuevo, cDetalle, IF(lAvance, 0, 1))
   oApp:oServer:CommitTransaction()
   Auditar(1," Pedido web N° "+ALLTRIM(STR(nId))+" -> "+cDetalle)
   Recargar(nId)
CATCH oError
   oApp:oServer:RollBack()
   ValidaError(oError)
END TRY
RETURN nil

************************************************************************************************
** Generar el envio en MiCorreo. Las credenciales del comercio las tiene el panel (cifradas),
** asi que gestion le pide al panel que lo haga:
**    POST {URL_PANEL}/api/gestion/tienda/{cId}/pedidos/{idpedido}/envio-micorreo
**    Header X-Gestion-Token: TOKEN_GESTION
**    Respuesta JSON: { "ok": true|false, "mensaje": "..." }
** (endpoint pendiente de crear en el panel; llama a TiendaCorreoService::importarPedido)
STATIC FUNCTION GenerarEnvioMiCorreo()
LOCAL nId, oHttp, cUrl, cResp := "", hResp, lOk := .f., cMsg := ""
IF oQryBrw:RecCount() == 0
   RETURN nil
ENDIF
nId := oQryBrw:idpedido
IF oQryBrw:entrega_modo != EM_CORREO .or. oQryBrw:envio_transportista != "correo_argentino"
   MsgInfo("Este pedido no se entrega por Correo Argentino.","MiCorreo")
   RETURN nil
ENDIF
IF !EMPTY(oQryBrw:envio_importado_txt)
   MsgInfo("El envio de este pedido ya fue importado a MiCorreo el "+oQryBrw:envio_importado_txt+".","MiCorreo")
   RETURN nil
ENDIF
IF oQryBrw:pago_estado == PG_PENDIENTE .or. oQryBrw:pago_estado == PG_RECHAZADO
   MsgStop("El pago del pedido todavia no esta confirmado. Confirmalo antes de generar el envio.","MiCorreo")
   RETURN nil
ENDIF
IF !MsgYesNo("Importar el envio del pedido N° "+ALLTRIM(STR(nId))+" a la cuenta MiCorreo del comercio?"+CHR(10)+CHR(10)+;
             "Queda cargado ahi para pagarlo e imprimir el rotulo desde la web de MiCorreo.","MiCorreo")
   RETURN nil
ENDIF
cUrl := URL_PANEL+"/api/gestion/tienda/"+ALLTRIM(oApp:cId)+"/pedidos/"+ALLTRIM(STR(nId))+"/envio-micorreo"
CursorWait()
TRY
   oHttp := CreateObject("MSXML2.ServerXMLHTTP.6.0")
   oHttp:Open("POST", cUrl, .F.)
   oHttp:SetRequestHeader("Content-Type", "application/json")
   oHttp:SetRequestHeader("Accept", "application/json")
   oHttp:SetRequestHeader("X-Gestion-Token", TOKEN_GESTION)
   oHttp:Send('{"usuario":'+JsonStr(ALLTRIM(oApp:usuario))+'}')
   cResp := oHttp:ResponseText
CATCH
   TRY
      oHttp := CreateObject("WinHttp.WinHttpRequest.5.1")
      oHttp:Open("POST", cUrl, .F.)
      oHttp:SetRequestHeader("Content-Type", "application/json")
      oHttp:SetRequestHeader("Accept", "application/json")
      oHttp:SetRequestHeader("X-Gestion-Token", TOKEN_GESTION)
      oHttp:Send('{"usuario":'+JsonStr(ALLTRIM(oApp:usuario))+'}')
      cResp := oHttp:ResponseText
   CATCH
      cResp := ""
   END TRY
END TRY
CursorArrow()
IF EMPTY(cResp)
   MsgStop("No se pudo conectar con el panel web para generar el envio."+CHR(10)+;
           "Revisa la conexion a internet o hacelo desde la web de MiCorreo.","MiCorreo")
   RETURN nil
ENDIF
hResp := {=>}
TRY
   hb_jsonDecode(cResp, @hResp)
   IF ValType(hResp) == "H"
      lOk  := hb_HGetDef(hResp, "ok", .f.) == .t.
      cMsg := hb_HGetDef(hResp, "mensaje", "")
   ENDIF
CATCH
   lOk := .f.
END TRY
IF lOk
   MsgInfo(IF(EMPTY(cMsg),"Envio importado a MiCorreo.",cMsg),"MiCorreo")
   Auditar(1," Envio MiCorreo generado pedido web N° "+ALLTRIM(STR(nId)))
ELSE
   MsgStop(IF(EMPTY(cMsg),"El panel respondio con un error: "+LEFT(cResp,300),cMsg),"MiCorreo")
ENDIF
Recargar(nId)
RETURN nil

************************************************************************************************
** Pagos a cuenta que los mayoristas informan desde su portal (transferencias). Confirmarlos aca
** NO genera el recibo: hay que cargarlo en gestion como cobro para que baje el saldo.
STATIC FUNCTION PagosCuenta()
LOCAL oForm, oFont, oFont1, oQry, oBrw, oBot := ARRAY(4), lHay := .f.
TRY
   oQry := oApp:oServer:Query("SELECT pc.id, pc.codcli, IFNULL(c.NOMBRE,'') AS cliente, IFNULL(tc.email,'') AS email, "+;
                              " pc.importe, IFNULL(pc.importe_unico,0) AS importe_unico, pc.medio, pc.estado, "+;
                              " IFNULL(pc.referencia,'') AS referencia, IFNULL(pc.comprobante,'') AS comprobante, "+;
                              " IFNULL(pc.observa,'') AS observa, DATE_FORMAT(pc.creado,'%d/%m/%Y %H:%i') AS creado_txt, "+;
                              " IFNULL(DATE_FORMAT(pc.confirmado,'%d/%m/%Y %H:%i'),'') AS confirmado_txt, IFNULL(pc.usuario,'') AS usuario, "+;
                              " IFNULL(c.SALDO,0) AS saldo_cliente "+;
                              " FROM ge_"+oApp:cId+"tienda_pagos_cuenta pc "+;
                              " LEFT JOIN ge_"+oApp:cId+"clientes c ON c.CODIGO = pc.codcli "+;
                              " LEFT JOIN ge_"+oApp:cId+"tienda_compradores tc ON tc.id = pc.comprador_id "+;
                              " ORDER BY pc.estado = 'pendiente' DESC, pc.id DESC LIMIT 200")
   lHay := .t.
CATCH
   MsgInfo("Este comercio no tiene el portal de mayoristas (sin tabla de pagos a cuenta).","Pagos a cuenta")
   RETURN nil
END TRY

DEFINE FONT oFont  NAME "TAHOMA" SIZE 0,-11.5
DEFINE FONT oFont1 NAME "Segoe UI Light" SIZE 0,-20
DEFINE DIALOG oForm TITLE "Pagos a cuenta informados por mayoristas" FROM 03,10 TO 30,135 OF oWnd1 FONT oFont
   @ 05, 05 SAY "PAGOS A CUENTA" OF oForm SIZE 300,14 PIXEL FONT oFont1
   @ 22, 05 SAY "El cliente informa la transferencia desde su portal. Confirmala cuando la veas acreditada y despues cargala en gestion como cobro para que baje su saldo." ;
       OF oForm SIZE 480,10 PIXEL
   @ 36, 05 XBROWSE oBrw SIZE 490,150 PIXEL OF oForm DATASOURCE oQry ;
      COLUMNS "creado_txt","codcli","cliente","importe","importe_unico","medio","estado","referencia","saldo_cliente","confirmado_txt","usuario" ;
      HEADERS "Informado","Cod.","Cliente","Importe","Importe c/centavos","Medio","Estado","Referencia","Saldo cliente","Confirmado","Usuario" ;
      SIZES 90,45,180,80,90,80,70,120,80,90,80 FOOTERS CELL LINES NOBORDER AUTOSORT
   oBrw:aCols[4]:nFooterType := AGGR_SUM
   oBrw:bClrStd := { || IF(oQry:RecCount()>0 .and. oQry:estado == PG_PENDIENTE, { CLR_BLACK, RGB(255,235,140) },;
                        If( oBrw:KeyNo() % 2 == 0, { CLR_BLACK, RGB(193,221,255) }, { CLR_BLACK, RGB(221,245,255) } )) }
   oBrw:MakeTotals()
   oBrw:CreateFromCode()
   PintaBrw(oBrw,0)
   @ 192, 05 BUTTON oBot[1] PROMPT "&Confirmar"   OF oForm SIZE 45,11 PIXEL ;
       ACTION (ConfirmarPagoCuenta(oQry), oBrw:Refresh()) WHEN(oQry:RecCount()>0 .and. oQry:estado == PG_PENDIENTE)
   @ 192, 55 BUTTON oBot[2] PROMPT "&Rechazar"    OF oForm SIZE 45,11 PIXEL ;
       ACTION (RechazarPagoCuenta(oQry), oBrw:Refresh()) WHEN(oQry:RecCount()>0 .and. oQry:estado == PG_PENDIENTE)
   @ 192,105 BUTTON oBot[3] PROMPT "Compro&bante" OF oForm SIZE 45,11 PIXEL ;
       ACTION VerComprobante(oQry:comprobante) WHEN(oQry:RecCount()>0 .and. !EMPTY(oQry:comprobante))
   @ 192,450 BUTTON oBot[4] PROMPT "Cerrar" OF oForm SIZE 40,11 PIXEL ACTION oForm:End() CANCEL
ACTIVATE DIALOG oForm CENTER
oQry:End()
RETURN nil

STATIC FUNCTION ConfirmarPagoCuenta(oQry)
LOCAL oError, nId := oQry:id
IF !MsgYesNo("Confirmar el pago a cuenta N° "+ALLTRIM(STR(nId))+" de "+ALLTRIM(oQry:cliente)+CHR(10)+;
             "por $ "+ALLTRIM(TRANSFORM(oQry:importe,"@E 999,999,999.99"))+"?"+CHR(10)+CHR(10)+;
             "Acordate de cargarlo en gestion como cobro para que baje el saldo del cliente.","Pago a cuenta")
   RETURN nil
ENDIF
TRY
   oApp:oServer:Execute("UPDATE ge_"+oApp:cId+"tienda_pagos_cuenta SET estado = '"+PG_APROBADO+"', confirmado = NOW(), "+;
                        " usuario = "+ClipValue2Sql(ALLTRIM(oApp:usuario))+" WHERE id = "+ClipValue2Sql(nId))
   Auditar(1," Pago a cuenta web N° "+ALLTRIM(STR(nId))+" confirmado ("+ALLTRIM(oQry:cliente)+")")
   oQry:Refresh()
CATCH oError
   ValidaError(oError)
END TRY
RETURN nil

STATIC FUNCTION RechazarPagoCuenta(oQry)
LOCAL oError, nId := oQry:id, cMotivo
cMotivo := PedirMotivo("Rechazar pago a cuenta","Motivo (opcional):")
IF cMotivo == NIL
   RETURN nil
ENDIF
IF !MsgNoYes("Rechazar el pago a cuenta N° "+ALLTRIM(STR(nId))+" de "+ALLTRIM(oQry:cliente)+"?","Pago a cuenta")
   RETURN nil
ENDIF
TRY
   oApp:oServer:Execute("UPDATE ge_"+oApp:cId+"tienda_pagos_cuenta SET estado = '"+PG_RECHAZADO+"', confirmado = NOW(), "+;
                        " usuario = "+ClipValue2Sql(ALLTRIM(oApp:usuario))+", observa = "+ClipValue2Sql(ALLTRIM(cMotivo))+;
                        " WHERE id = "+ClipValue2Sql(nId))
   Auditar(1," Pago a cuenta web N° "+ALLTRIM(STR(nId))+" rechazado")
   oQry:Refresh()
CATCH oError
   ValidaError(oError)
END TRY
RETURN nil

************************************************************************************************
** Evento en el historial del pedido (tienda_eventos), origen 'comercio' con el usuario de gestion.
** nNotificado: 0 = el panel tiene que avisar al comprador; 1 = no hace falta avisar.
STATIC FUNCTION RegistrarEvento(nId, cEstado, cDetalle, nNotificado)
DEFAULT nNotificado := 1
oApp:oServer:Execute("INSERT INTO ge_"+oApp:cId+"tienda_eventos (idpedido, estado, detalle, origen, usuario, notificado, fecha) VALUES ("+;
                     ClipValue2Sql(nId)+", "+ClipValue2Sql(cEstado)+", "+ClipValue2Sql(LEFT(cDetalle,255))+", 'comercio', "+;
                     ClipValue2Sql(LEFT(ALLTRIM(oApp:usuario),60))+", "+ClipValue2Sql(nNotificado)+", NOW())")
RETURN nil

************************************************************************************************
** Reglas de estados web (copia de TiendaSeguimientoService del panel: si cambian alla, cambiar aca)
STATIC FUNCTION NormalizarEstadoWeb(cEstado)
cEstado := ALLTRIM(IF(cEstado == NIL,"",cEstado))
IF cEstado == "enviado"
   RETURN EW_DESPACHADO
ENDIF
IF ASCAN({EW_RECIBIDO,EW_PREPARACION,EW_LISTO,EW_DESPACHADO,EW_EN_CAMINO,EW_ENTREGADO,EW_CANCELADO}, cEstado) == 0
   RETURN EW_RECIBIDO
ENDIF
RETURN cEstado

STATIC FUNCTION FlujoWeb(cModo)
DO CASE
   CASE cModo == EM_RETIRO ; RETURN {EW_RECIBIDO, EW_PREPARACION, EW_LISTO, EW_ENTREGADO}
   CASE cModo == EM_FLOTA  ; RETURN {EW_RECIBIDO, EW_PREPARACION, EW_LISTO, EW_EN_CAMINO, EW_ENTREGADO}
   CASE cModo == EM_CORREO ; RETURN {EW_RECIBIDO, EW_PREPARACION, EW_LISTO, EW_DESPACHADO, EW_ENTREGADO}
ENDCASE
RETURN {EW_RECIBIDO, EW_PREPARACION, EW_LISTO, EW_EN_CAMINO, EW_ENTREGADO}

STATIC FUNCTION SiguientesWeb(cActual, cModo)
LOCAL aFlujo, nPos
IF cActual == EW_CANCELADO .or. cActual == EW_ENTREGADO
   RETURN {}
ENDIF
aFlujo := FlujoWeb(cModo)
nPos   := ASCAN(aFlujo, cActual)
IF nPos == 0
   RETURN {EW_PREPARACION}
ENDIF
IF nPos == LEN(aFlujo)
   RETURN {}
ENDIF
IF cActual == EW_LISTO .and. cModo == EM_A_CONVENIR
   RETURN {EW_EN_CAMINO, EW_DESPACHADO, EW_ENTREGADO}
ENDIF
RETURN {aFlujo[nPos+1]}

STATIC FUNCTION AnteriorWeb(cActual, cModo)
LOCAL aFlujo, nPos
IF cActual == EW_CANCELADO .or. cActual == EW_RECIBIDO
   RETURN NIL
ENDIF
aFlujo := FlujoWeb(cModo)
nPos   := ASCAN(aFlujo, cActual)
IF nPos == 0
   RETURN EW_LISTO   // en_camino / despachado fuera de su flujo (a convenir): vuelve a listo
ENDIF
IF nPos == 1
   RETURN NIL
ENDIF
RETURN aFlujo[nPos-1]

STATIC FUNCTION EtiquetaWeb(cEstado, cModo)
cEstado := NormalizarEstadoWeb(cEstado)
IF cModo == EM_RETIRO
   IF cEstado == EW_LISTO
      RETURN "Listo para retirar"
   ENDIF
   IF cEstado == EW_ENTREGADO
      RETURN "Retirado"
   ENDIF
ENDIF
IF cEstado == EW_LISTO .and. (cModo == EM_FLOTA .or. cModo == EM_CORREO)
   RETURN "Listo para salir"
ENDIF
DO CASE
   CASE cEstado == EW_RECIBIDO    ; RETURN "Recibido"
   CASE cEstado == EW_PREPARACION ; RETURN "En preparacion"
   CASE cEstado == EW_LISTO       ; RETURN "Listo"
   CASE cEstado == EW_DESPACHADO  ; RETURN "Despachado"
   CASE cEstado == EW_EN_CAMINO   ; RETURN "En camino"
   CASE cEstado == EW_ENTREGADO   ; RETURN "Entregado"
   CASE cEstado == EW_CANCELADO   ; RETURN "Cancelado"
ENDCASE
RETURN cEstado

STATIC FUNCTION EtiquetaGestion(cEstado)
DO CASE
   CASE cEstado == "P" ; RETURN "Pendiente"
   CASE cEstado == "A" ; RETURN "En armado"
   CASE cEstado == "F" ; RETURN "Facturado"
   CASE cEstado == "N" ; RETURN "Anulado"
   CASE cEstado == "R" ; RETURN "Rechazado"
   CASE cEstado == ESTADO_WEB_REVISION ; RETURN "Web en revision"
ENDCASE
RETURN cEstado

************************************************************************************************
** Pide un texto corto (motivo). Devuelve el texto sin espacios (puede ser vacio) o NIL si cancelo.
STATIC FUNCTION PedirMotivo(cTitulo, cTexto)
LOCAL oForm, oFont, oGet, oBot := ARRAY(2), acor, lRta := .f., cMotivo := SPACE(120)
DEFINE FONT oFont NAME "TAHOMA" SIZE 0,-11.5
DEFINE DIALOG oForm TITLE cTitulo FROM 05,15 TO 14,75 OF oWnd1 FONT oFont
   @ 05, 05 SAY cTexto OF oForm PIXEL SIZE 220,10
   @ 18, 05 GET oGet VAR cMotivo OF oForm PIXEL SIZE 220,12
   acor := AcepCanc(oForm)
   @ acor[1],acor[2] BUTTON oBot[1] PROMPT "&Aceptar"  OF oForm SIZE 30,10 ACTION ((lRta := .t.), oForm:End()) PIXEL
   @ acor[3],acor[4] BUTTON oBot[2] PROMPT "&Cancelar" OF oForm SIZE 30,10 ACTION ((lRta := .f.), oForm:End()) PIXEL CANCEL
ACTIVATE DIALOG oForm CENTER ON INIT oGet:SetFocus()
IF !lRta
   RETURN NIL
ENDIF
RETURN ALLTRIM(cMotivo)

************************************************************************************************
** Cadena JSON con comillas y barras escapadas
STATIC FUNCTION JsonStr(cTexto)
cTexto := STRTRAN(cTexto, "\", "\\")
cTexto := STRTRAN(cTexto, '"', '\"')
RETURN '"'+cTexto+'"'

************************************************************************************************
** Cierre de la ventana: libera el query y se saca de la lista de ventanas abiertas
STATIC FUNCTION cerrar()
LOCAL aNueva := {}, i, j
   oQryBrw:End()
   RELEASE oQryBrw
   j := ASCAN(oApp:aVentanas,cVentana)
   FOR i := 1 TO LEN(oApp:aVentanas)
       IF i <> j
          AADD(aNueva,oApp:aVentanas[i])
       ENDIF
   NEXT i
   oApp:aVentanas := ACLONE(aNueva)
RETURN .t.
