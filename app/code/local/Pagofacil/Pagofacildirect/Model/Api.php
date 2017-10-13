<?php
/**
 * API para el consumo de los servicios
 * de PagoFacil
 * @author ivelazquex <isai.velazquez@gmail.com>
 */
class Pagofacil_Pagofacildirect_Model_Api
{
    // --- ATTRIBUTES ---
    /**
     * URL del servicio de PagoFacil para pruebas
     * @var string
     */

    //protected $_urlDemo = 'https://stapi.pagofacil.net/Wsrtransaccion/index/format/json';
    protected $_urlDemo = 'http://core.dev/Magento/Magento/index/format/json';

    protected $_urlVerify = 'http://core.dev/Magento/Magento/querytrans/';

    /**
     * URL del servicio de PagoFacil en ambiente de produccion
     * @var string 
     */
    protected $_urlProd = 'https://www.pagofacil.net/ws/public/Wsrtransaccion/index/format/json';

    /**
     * respuesta sin parsear del servicio
     * @var string
     */
    protected $_response = NULL;    
    
    public function __construct()
    {
        
    }
    
    /**
     * consume el servicio de pago de PagoFacil
     * @param string[] vector con la informacion de la peticion
     * @return mixed respuesta del consumo del servicio
     * @throws Exception
     */
    public function payment($info)
    {
        $response = null;

        if (!is_array($info))
        {
            throw new Exception('parameter is not an array');
        }

        // Determina el entorno 
        $ambiente = ($info['prod'] == '1') ? $this->_urlProd : $this->_urlDemo;

        // Lanza la transaccion
        $query     = $this->buildParams($this->infoBuild('data', $info));
        $consumeWS = json_decode($this->consumeWsPost($ambiente, $query),true);

        if (is_array($consumeWS)) {
            return $consumeWS['WebServices_Transacciones']['transaccion'];
        }

        //Verifica si la transaccion y responde
        $response = $this->verifyTransactionMagento('verify' ,$info);

        return $response;
    }

    /**
     * Envía la solicitud para consumir el sw de transacciones magento
     * @param 
     * @return 
     */
    private function consumeWsPost($url, $params)
    {
        $url = $url.'/?method=transaccion';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->_response = curl_exec($ch);
        curl_close($ch);

        return $this->_response;

    }
    
    /**
     * obtiene la respuesta del servicio
     * @return string
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * consume el servicio de pago en efectivo de PagoFacil
     * @param string[] vector con la informacion de la peticion
     * @return mixed respuesta del consumo del servicio
     * @throws Exception
     */
     public function paymentCash($info)
    {
        $response = null;        
        
        if (!is_array($info))
        {
            throw new Exception('parameter is not an array');
        }

        $info['url'] = 'https://www.pagofacil.net/ws/public/cash/charge';
        // determinar si el entorno es para pruebas
        if ($info['prod'] == '0')
        {
            $info['url'] = 'https://stapi.pagofacil.net/cash/charge';
        }

        // datos para la peticion del servicio
        $data = array(
            'branch_key'       => $info['branch_key'],
            'user_key'         => $info['user_key'],
            'order_id'         => $info['order_id'],
            'product'          => $info['product'],
            'amount'           => $info['amount'],
            'store_code'       => $info['storeCode'],
            'customer'         => $info['customer'],
            'email'            => $info['email']
        );

        // consumo del servicio
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $info['url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Blindly accept the certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->_response = curl_exec($ch);
        curl_close($ch);

        // tratamiento de la respuesta del servicio
        $response = json_decode($this->_response,true);               

        return $response;
    }

    /**
     * Arreglos de parametros en el ws
     * @param 
     * @return 
     */
    private function infoBuild($tipo, array $info )
    {

         // Datos para la peticion del servicio
        $data = array(
            'idServicio'        => '3',
            'idSucursal'        => $info['idSucursal'],
            'idUsuario'         => $info['idUsuario'],
            'nombre'            => $info['nombre'],
            'apellidos'         => $info['apellidos'],
            'numeroTarjeta'     => $info['numeroTarjeta'],
            'cvt'               => $info['cvt'],
            'cp'                => $info['cp'],
            'mesExpiracion'     => $info['mesExpiracion'],
            'anyoExpiracion'    => $info['anyoExpiracion'],
            'monto'             => 1,
            'email'             => $info['email'],
            'telefono'          => $info['telefono'],
            'celular'           => $info['celular'],
            'calleyNumero'      => $info['calleyNumero'],
            'colonia'           => $info['colonia'],
            'municipio'         => $info['municipio'],
            'estado'            => $info['estado'],
            'pais'              => $info['pais'],
            'idPedido'          => $info['idPedido'],
            'ip'                => $info['ipBuyer'],
            'noMail'            => $info['noMail'],
            'plan'              => $info['plan'],
            'mensualidades'     => $info['mensualidades'],
        );

        // Datos para la verificacion de una transaccion
        $verify = array(
            'idSucursal'        => $info['idSucursal'],
            'idUsuario'         => $info['idUsuario'],
            'monto'             => 1,
            'idPedido'          => $info['idPedido'],
        );

        $arrayRegresa = ($tipo == 'data') ? $data : $verify;

        return $arrayRegresa;

    }

    /**
     * Construye el querystring de parametros a enviar en el ws
     * @param array de datos
     * @return querystring
     */
    private function buildParams($data)
    {

        $query = '';
        foreach ($data as $key=>$value){
            $query .= sprintf("&data[%s]=%s", $key, urlencode($value));
        }

        return $query;

    }

    /**
     * Consume el se de magento verificando si la transaccion existe
     * @param tipo de arreglo, array de informacion general
     * @return resultado de la consulta al sw
     */
    private function verifyTransactionMagento($type, array $info)
    {

        $query        = $this->buildParams($this->infoBuild($type, $info));
        $respVerifyWS = json_decode($this->consumeWsPost($this->_urlVerify, $query),true);

        return $respVerifyWS;

    }


}