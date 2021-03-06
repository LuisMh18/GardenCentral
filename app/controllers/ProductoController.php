<?php
class ProductoController extends \BaseController {

    //mostrar el total
    private function total(){
      /*  $cart = \Session::get('cart');
        $total = 0;
        foreach($cart as $item){
            if($item->iva0 == 0){
            } else {
                $m = $item->precio * $item->descuento;
                $total += ($item->precio - $m) * $item -> quantity * 0.16;
            }
        }
        return $total;*/
    }


    public function terminos(){
        return View::make('users/terminos');
    }
    public function verterminos(){
        $terminos = DB::table('paginas')
                    ->where('estatus', 1)
                    ->first();
        return Response::json($terminos);
    }


    //Mostramos el contenido del carrito
    public function getIndex(){
        if (Auth::check()) {
            $iduser = Auth::user()->id;
            $idcliente = DB::table('cliente')
                   ->where("usuario_id", $iduser)->pluck('id');
            $direcfiscal = DB::table('direccion_cliente')
                                ->where("tipo", "Fiscal")
                                ->where("cliente_id", $idcliente)->get();
            $pago = FormaDePago::all();
           $cart = \Session::get('cart');
           $extras = \Session::get('extras');
           $total = $this->total();
           //Extras
           $p = DB::table('producto')
                    ->where('nombre', 'Extras')
                    ->select('clave')
                    ->get();
                   return View::make('users/index',
                        compact(
                            'cart',
                            'extras',
                            'total',
                            'pago',
                            'direccion',
                            'estado',
                            'telcliente',
                            'direcfiscal',
                            'p'
                        ));
        } else {
            return Redirect::to('login');
        }
    }


    //Alertas
    public function verificarproducto(){
        $inv = DB::table('inventario')
                ->count();
            $p = DB::table('producto')
                    ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
                    ->orderBy('producto.cantidad_minima', 'asc')
                    ->where('producto.id', 19)
                    ->pluck('cantidad_minima');
            $inventario = DB::table('producto')
                    ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
                    ->orderBy('producto.cantidad_minima', 'asc')
                    ->where('producto.id', 19)
                    ->pluck('cantidad');
            //$compararamos
            if($inventario <= $p){
                //verificamos si e producto ya existe
                $i_v = DB::table('alertas')
                ->where('producto_id', 19)
                ->first();
                //Si no existe insertamos
                if(count($i_v)==0){
                   /* $alerta = new Alerta;
                    $alerta->producto_id = $p3;
                    $alerta->estatus = 1;
                    $alerta->save();*/
                    echo "No existe";
                //si ya existe no pasa nada
                } else {
                    echo "ya existe";
                }
            } else {
                $c = "Hay suficientes productos";
                echo $c;
            }

    }


    //Listar pedidos del cliente
    public function listarpedidos(){
        $id = Auth::user()->id;

        $cli = DB::table('cliente')
            ->where('usuario_id', $id)
            ->pluck('id');

        $pedido = DB::table('pedido')
                    ->join('mensajeria', 'pedido.mensajeria_id','=','mensajeria.id')
                    ->select('pedido.id','num_pedido', 'pedido.created_at','mensajeria.nombre', 'pedido.estatus', 'total')
                    ->Where('cliente_id', $cli)
                    ->Where('estatus', '!=', 4)
                    ->get();

        echo json_encode($pedido);
    }


    public function detalledelpedido(){
        $id = Input::get('id');
        //Obtenemos el id del producto detalle
        $d = DB::table('pedido')
                ->join('pedido_detalle','pedido.id', '=','pedido_detalle.pedido_id')
                ->where('pedido.id', $id)
                ->pluck('pedido_detalle.id');
        $idd = DB::table('pedido')
                ->join('pedido_detalle','pedido.id', '=','pedido_detalle.pedido_id')
                ->where('pedido.id', $id)
                ->pluck('pedido_detalle.producto_id');
        $iddirec = DB::table('pedido')
                ->join('direccion_cliente','pedido.direccion_cliente_id', '=','direccion_cliente.id')
                ->where('pedido.id', $id)
                ->pluck('pedido.direccion_cliente_id');
        $pedimento = DB::table('pedido_detalle')
                        ->join('producto', 'pedido_detalle.producto_id', '=', 'producto.id')
                        ->where('pedido_detalle.pedido_id', $id)
                        ->select('clave', 'num_pedimento', 'cantidad')
                        ->get();
        if($iddirec == null){
            $t = 'tienda';
            $domi = DB::table('cliente')
            ->join('pedido', 'cliente.id', '=', 'pedido.cliente_id')
            ->join('usuario', 'cliente.usuario_id', '=', 'usuario.id')
            ->where("pedido.id", $id)
            ->get();
        } else {
            $t = 'domicilio';
        $domi = DB::table('direccion_cliente')
            ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
            ->join('usuario', 'cliente.usuario_id', '=', 'usuario.id')
            ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
            ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
            ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
            ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
            ->where("direccion_cliente.id", $iddirec)
            ->get();
        }
         $ped = DB::table('cliente')
            ->join('pedido','cliente.id', '=','pedido.cliente_id')
            ->where('pedido.id', $id)
            ->get();
        $pro = DB::table('producto')
                    ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                    ->where('pedido_detalle.pedido_id', $id)
                    ->select('pedido_id','clave', 'nombre', 'color', 'precio','iva0', 'foto', 'producto.id', 'cantidad', 'num_pedimento')
                    //->distinct()
                    ->get();
        $dpro = DB::table('pedido_detalle')
                    ->join('producto','pedido_detalle.producto_id', '=','producto.id')
                    ->where('pedido_detalle.id', $d)
                    ->get();
        return Response::json(array(
                    'dpro' => $dpro,
                    'pro' => $pro,
                    'domi' => $domi,
                    'ped' => $ped,
                    't' => $t,
                    'pedimento' => $pedimento
                ));
    }


//Listar las categorias
    //Listar coberturas
public function selectcategorias(){
    $categorias = DB::table('categoria')
        ->select('id', 'categoria')
        ->where('estatus', '1')
        ->where('categoria', '!=', 'extras')
        ->get();

    return Response::json(array('categorias' => $categorias));
}


public function listarproductoscategoria(){
    $id = Input::get('id');

    $id_user = Auth::user()->id;

    $nivel = DB::table('cliente')
            ->join('nivel_descuento', 'cliente.nivel_descuento_id', '=', 'nivel_descuento.id')
            ->select('descripcion')
            ->where('cliente.usuario_id', $id_user)
            ->pluck('descripcion');

    if($nivel == 'Retail'){

        $producto = DB::table('producto')
        ->join('producto_precio', 'producto.id', '=', 'producto_precio.producto_id')
        ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
        ->join('familia', 'producto.familia_id', '=', 'familia.id')
        ->select('producto.id', 'nombre', 'color', 'foto', 'iva0', 'cantidad', 'precio', 'clave', 'tipo')
        ->where('categoria_id', $id)
        ->where('tipo', 1)
        ->where('producto.estatus', 1)
        ->where('producto_precio.estatus', 1)
        ->get();

    } else if($nivel == 'Mayorista'){

        $producto = DB::table('producto')
        ->join('producto_precio', 'producto.id', '=', 'producto_precio.producto_id')
        ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
        ->join('familia', 'producto.familia_id', '=', 'familia.id')
        ->select('producto.id', 'nombre', 'color', 'foto', 'iva0', 'cantidad', 'precio', 'clave', 'tipo')
        ->where('categoria_id', $id)
        ->where('tipo', 2)
        ->where('producto.estatus', 1)
        ->where('producto_precio.estatus', 1)
        ->get();

    } else if($nivel == 'Distribuidor'){

        $producto = DB::table('producto')
        ->join('producto_precio', 'producto.id', '=', 'producto_precio.producto_id')
        ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
        ->join('familia', 'producto.familia_id', '=', 'familia.id')
        ->select('producto.id', 'nombre', 'color', 'foto', 'iva0', 'cantidad', 'precio', 'clave', 'tipo')
        ->where('categoria_id', $id)
        ->where('tipo', 3)
        ->where('producto.estatus', 1)
        ->where('producto_precio.estatus', 1)
        ->get();

    }


    return Response::json(array(

        'producto' => $producto

        ));
}



//Extras
    //Listar domiclios
    public function listardomicilios(){
        $iduser = Auth::user()->id; //id del usuario
    //lo usamos para obtener el id del cliente
    $idcliente = DB::table('cliente')
            ->where("usuario_id", $iduser)->pluck('id');
        $direc = DB::table('direccion_cliente')
            ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
            ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
            ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
            ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
            ->select('direccion_cliente.id','pais','estados','municipio', 'calle1', 'calle2', 'colonia','delegacion','codigo_postal','numero','tipo')
            ->where('direccion_cliente.estatus',1)
            ->where("direccion_cliente.cliente_id", $idcliente)->get();
        return Response::json(array('direc' => $direc));
    }


    public function eliminardomicilio(){
        $id = Input::get('idd');
        $estatus = 0;
        //Actualizamos la direccion
        $direccion = DireccionCliente::find($id);
        $direccion->estatus = $estatus;
        $direccion->save();
        return Response::json('eliminado');
    }


    //Listar telefonos
    public function listartelefonos(){
         $iduser = Auth::user()->id;
    $idcliente = DB::table('cliente')
            ->where("usuario_id", $iduser)->pluck('id');
        $telefono = DB::table('telefono_cliente')
                        ->where('cliente_id', $idcliente)->get();
        return Response::json(array('telefono' =>$telefono));
    }


    public function listnotas(){
        $seccion = Input::get('seccion');
        $nota = DB::table('notas')
                ->where('sección', $seccion)
                ->where('estatus', 1)
                ->orderBy('created_at', 'asc')
                ->select('texto')
                ->get();
        return Response::json(array('nota' => $nota));
    }

public function getVerificaremail(){
    $mail = Input::get('email');
    $resp = DB::table('usuario')
            ->select('email')
            ->where('email', $mail)->first();
    if(count($resp)==0){
        return Response::json($resp);
    } else {
        return Response::json($resp);
    }
  }


public function getProducto(){
    $id_user = Auth::user()->id;
    $clave = Input::get('clave');

    $nivel = DB::table('cliente')
            ->join('nivel_descuento', 'cliente.nivel_descuento_id', '=', 'nivel_descuento.id')
            ->select('descripcion')
            ->where('cliente.usuario_id', $id_user)
            ->pluck('descripcion');

    $p = DB::table('producto')
                ->where('clave', $clave)
                ->pluck('producto.id');

    //verificamos que el producto este disponible en el inventario
    $inve = DB::table('inventario')
                ->where('producto_id', $p)
                ->pluck('id');

    if($inve == ""){
        $producto = array('indefinido' => 'vacio');
        return Response::json(array('producto' => $producto));
    } else {
        if($nivel == 'Retail'){

        $producto = DB::table('producto')
                ->join('producto_precio', 'producto.id', '=', 'producto_precio.producto_id')
                ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
                ->select('producto.id', 'nombre', 'color', 'foto', 'piezas_paquete', 'clave', 'precio', 'tipo', 'cantidad', 'iva0')
                ->where('clave', $clave)
                ->where('tipo', 1)
                ->where('producto.estatus', 1)
                ->where('producto_precio.estatus', 1)
                ->get();

            return Response::json(array(
                'producto' => $producto,
                'nivel' => $nivel
                ));

    } else if($nivel == 'Mayorista') {
        $producto = DB::table('producto')
                ->join('producto_precio', 'producto.id', '=', 'producto_precio.producto_id')
                ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
                ->select('producto.id', 'nombre', 'color', 'foto', 'piezas_paquete', 'clave', 'precio', 'tipo', 'cantidad', 'iva0')
                ->where('clave', $clave)
                ->where('tipo', 2)
                ->where('producto.estatus', 1)
                ->where('producto_precio.estatus', 1)
                ->get();

            return Response::json(array(
                'producto' => $producto,
                'nivel' => $nivel
                ));
    } else if($nivel == 'Distribuidor'){
        $producto = DB::table('producto')
                ->join('producto_precio', 'producto.id', '=', 'producto_precio.producto_id')
                ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
                ->select('producto.id', 'nombre', 'color', 'foto', 'piezas_paquete', 'clave', 'precio', 'tipo', 'cantidad', 'iva0')
                ->where('clave', $clave)
                ->where('tipo', 3)
                ->where('producto.estatus', 1)
                ->where('producto_precio.estatus', 1)
                ->get();

            return Response::json(array(
                'producto' => $producto,
                'nivel' => $nivel
            ));

    }
}

 }


 //Estras
 public function mostrarextra(){
    $extra = DB::table('producto')
            ->where('nombre', 'like', '%extra%')
            ->select('clave')
            ->first();

        if(count($extra) == 0){
            $e = 'Vacio';
            return Response::json($e);
        } else {
            return Response::json($extra);
        }
 }


 //---Verificar teléfonos
 public function getVerificarTel(){
    $tel = Input::get('tel');
    //obtenemos el id del usuario que inicio sesion
        $idusuario = Auth::user()->id;
    //consulta para obtener el id del cliente del usuario que incio sesion
        $idcliente = DB::table('cliente')
            ->where('usuario_id', $idusuario)->pluck('id');
    $resp = DB::table('telefono_cliente')
            ->select('numero')
            ->where('numero', $tel)
            ->where('telefono_cliente.cliente_id', $idcliente)->first();
    if(count($resp)==0){
        return Response::json($resp);
    } else {
        return Response::json($resp);
    }
  }


  public function pedidoexistente($id){
        $email = Auth::user()->email;
        $idusuario = Auth::user()->id;

        $cotizar = Input::get('cotizar');
        $formapago = Input::get('formapago');
        $msjeria = Input::get('msjeria');
        $r_extra = Input::get('r_extra');
        $total = Input::get('total');

        $idpro = json_decode(Input::get('aInfo'));
        $data_extra = json_decode(Input::get('nExtra'));

         $mensajeria = new Mensajeria;
         $mensajeria->id = Input::get('id');
         $mensajeria->nombre = $msjeria;
         $mensajeria->save();

        $resp = DB::table('cliente')
            ->where('usuario_id', $idusuario)
            ->pluck('id');

         $num_ped = date('Y').date('m').date("d").date("H").date("i").date("s");


            $clienteformapago = new ClienteFormaPago;
            $clienteformapago->cliente_id = $resp;
            $clienteformapago->forma_pago_id = $formapago;
            $clienteformapago->save();

            $pedido = new Pedido;
            $pedido->cliente_id = $resp;
            $pedido->mensajeria_id = $mensajeria['id'];
            $pedido->direccion_cliente_id = " ";
            $pedido->forma_pago_id = $formapago;
            $pedido->num_pedido = $num_ped;
            $pedido->total = $total;
            $pedido->fecha_registro = date('Y-m-d');
            $pedido->anio_mes = date('Y-m');
            $pedido->anio = date('Y');
            $pedido->cotizar_envio = $cotizar;
            $pedido->extra_pedido = $r_extra;
            $pedido->observaciones =  " ";
            $pedido->save();

            //comprbamos si hay extras
            if($r_extra == 0){

            } else {
                for ($i=0; $i < count($data_extra); $i++) {

                    $extra = new ExtraPedido;
                    $extra->pedido_id = $pedido['id'];
                    $extra->clave = $data_extra[$i]->claveextra;
                    $extra->descripcion = $data_extra[$i]->contenido;
                    $extra->save();
                } //end for extras

            }
                for ($i=0; $i < count($idpro); $i++) {

                    //Insertamos en la tabla los datos temporales
                    $temporal = new TotalProducto;
                    $temporal->usuario_id = $idusuario;
                    $temporal->pedido_id = $pedido['id'];
                    $temporal->clave = $idpro[$i]->clave;
                    $temporal->cantidad = $idpro[$i]->cant;
                    $temporal->save();
                    //total producto
                    $total = DB::table('producto')
                            ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->pluck('cantidad');
                    //obtenemos el numero total de pedimentos
                    $pedimentos = DB::table('producto')
                            ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->count();
                    //Por cada pedimento del producto realizaremos un for
                    for($a = 0; $a < $pedimentos; $a++){
                        //obtenemos la cantidad del pedimento mas viejo
                        $cant = DB::table('producto')
                                ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                                ->where('producto.id', $idpro[$i]->idp)
                                ->orderBy('inventario_detalle.created_at', 'asc')
                                ->pluck('cantidad');

                        //obtenemos el pedimento mas viejo
                        $num_p = DB::table('producto')
                                ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                                ->where('producto.id', $idpro[$i]->idp)
                                ->orderBy('inventario_detalle.created_at', 'asc')
                                ->pluck('num_pedimento');
                        //obtenemos la cantidad almacenada temporalmente
                        $y1 = DB::table('total_producto')
                            ->where('usuario_id', $idusuario)
                            ->where('pedido_id', $pedido['id'])
                            ->where('clave', $idpro[$i]->clave)
                            ->pluck('cantidad');
                        //comparamos si la cantidad de productos elegida por el usuario es
                        //menor o igual a la cantidad del pedimento mas viejp
                        if($y1 <= $cant){
                         // echo " | Si es menor oh igual ";//obtenemos los id del producto
                        //Con el id del producto obtenemos el id del inventario
                        $inve1 = DB::table('inventario')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->pluck('id');
                        //descontamos la cantidad del inventario
                        $inv = Inventario::find($inve1);
                        $inv->cantidad -= $y1;
                        $inv->save();
                        //Obtenemos el id del inventario detalle
                        $id_i_d = DB::table('inventario_detalle')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('num_pedimento', $num_p)
                                ->orderBy('created_at', 'asc')
                                ->pluck('id');
                         //Consulta a la tabla precio
                        $pre_id = DB::table('producto_precio')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('tipo', $idpro[$i]->tipoprecio)
                                ->pluck('id');
                         //registramos en pedido detalle
                        $p_detalle = new PedidoDetalle;
                        $p_detalle->pedido_id = $pedido['id'];
                        $p_detalle->producto_id = $idpro[$i]->idp;
                        $p_detalle->producto_precio_id = $pre_id;
                        $p_detalle->precio = $idpro[$i]->preciop;
                        $p_detalle->num_pedimento = $num_p;
                        $p_detalle->cantidad = $y1;
                        $p_detalle->save();
                        //descontamos la cantidad del inventario_detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->cantidad -= $y1;
                        $inv_d->save();
                        //eliminamos los datos temporales
                        $idy = DB::table('total_producto')
                            ->where('usuario_id', $idusuario)
                            ->where('pedido_id', $pedido['id'])
                            ->where('clave', $idpro[$i]->clave)
                            ->pluck('id');
                        $y1 = TotalProducto::find($idy);
                        $y1->delete();
                        //comprobamos que no haya productos en el inventario detalle con cantidad de 0
                        $id_i_d = DB::table('inventario_detalle')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->where('num_pedimento', $num_p)
                                    ->where('cantidad', 0)
                                    ->pluck('id');
                       if(count($id_i_d ) == 0){
                        } else { //si hay
                            //Borramos el producto
                            $inv_d = InventarioDetalle::find($id_i_d);
                            $inv_d->delete();

                        }
                        //Alertas de los productos
                        $p = DB::table('producto')
                            ->orderBy('producto.cantidad_minima', 'asc')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->pluck('cantidad_minima');
                        $inventario = DB::table('inventario')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->pluck('cantidad');
                        //Comparamos la cantidad actual del producto del inventario con la cantidad minima del producto
                        if($inventario <= $p){
                            //verificamos si e producto ya existe
                            $i_v = DB::table('alertas')
                            ->where('producto_id', $idpro[$i]->idp)
                            ->first();
                            //Si no existe insertamos
                            if(count($i_v)==0){
                                $alerta = new Alerta;
                                $alerta->producto_id = $idpro[$i]->idp;
                                $alerta->estatus = 1;
                                $alerta->save();
                            //si ya existe no pasa nada
                            } else {

                            }


                        }
                        break; //Detenemos el ciclo -------------------------------------
                        } else { //si no es menor
                          //echo " | Es mayor, faltan productos";
                            //Con el id del producto obtenemos el id del inventario
                        $inve1 = DB::table('inventario')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->pluck('id');
                         //le restamos a la cantidad del producto del cliente la cantidad del pedimento
                         $y_m = $y1 - $cant;
                         //descontamos la cantidad del inventario
                        $inv = Inventario::find($inve1);
                        $inv->cantidad -= $cant;
                        $inv->save();
                        //---Cantidad del producto en el inventario
                        $t_i = DB::table('inventario')
                                ->where('id', $inve1)
                                ->pluck('cantidad');
                        //Obtenemos el id del inventario detalle
                        $id_i_d = DB::table('inventario_detalle')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('num_pedimento', $num_p)
                                ->orderBy('created_at', 'asc')
                                ->pluck('id');
                        //descontamos la cantidad del inventario_detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->cantidad -= $y_m;
                        $inv_d->save();
                        //Consulta a la tabla precio
                        $pre_id = DB::table('producto_precio')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('tipo', $idpro[$i]->tipoprecio)
                                ->pluck('id');
                        //Registramos en el pedido detalle
                        $p_detalle = new PedidoDetalle;
                        $p_detalle->pedido_id = $pedido['id'];
                        $p_detalle->producto_id = $idpro[$i]->idp;
                        $p_detalle->producto_precio_id = $pre_id;
                        $p_detalle->precio = $idpro[$i]->preciop;
                        $p_detalle->num_pedimento = $num_p;
                        $p_detalle->cantidad = $cant;
                        $p_detalle->save();
                        //Borramos el producto del inventario detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->delete();
                        //Actualizamos la cantidad de los datos temporales
                        $id_t = DB::table('total_producto')
                                ->where('usuario_id', $idusuario)
                                ->where('pedido_id', $pedido['id'])
                                ->where('clave', $idpro[$i]->clave)
                                ->pluck('id');

                        $y_a = TotalProducto::find($id_t);
                        $y_a->cantidad -= $cant;
                        $y_a->save();
                        $cant_a = DB::table('total_producto')
                                ->where('usuario_id', $idusuario)
                                ->where('pedido_id', $pedido['id'])
                                ->where('clave', $idpro[$i]->clave)
                                ->pluck('cantidad');
                        } //else si es mayor
                    } //END FOR PEDIMENTOS
            } //end for principal


        $id = $pedido['id'];
            $iddirec = DB::table('pedido')
                    ->join('direccion_cliente','pedido.direccion_cliente_id', '=','direccion_cliente.id')
                    ->where('pedido.id', $id)
                    ->pluck('pedido.direccion_cliente_id');
            $pedido = DB::table('pedido')
                        ->where('pedido.id', $id)
                        ->get();
                 $cli = DB::table('cliente')
                    ->join('pedido', 'cliente.id', '=', 'pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                 $domi = DB::table('direccion_cliente')
                    ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
                    ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
                    ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
                    ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
                    ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
                    ->where("direccion_cliente.id", $iddirec)
                    ->get();
                 $ped = DB::table('cliente')
                    ->join('pedido','cliente.id', '=','pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                  $pro = DB::table('producto')
                              ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                                ->where('pedido_detalle.pedido_id', $id)
                                ->select('clave', 'nombre', 'color', 'pedido_detalle.precio','iva0', 'cantidad', 'num_pedimento')
                                ->get();
                $extra = DB::table('extra_pedido')
                        ->where('pedido_id', $id)
                        ->get();

                $dpro = DB::table('pedido_detalle')
                            ->join('producto','pedido_detalle.producto_id', '=','producto.id')
                            ->where('pedido_detalle.id', $id)
                            ->get();
                   //Sacamos el iva
                    $total = 0;
                    foreach($pro as $item){
                    if($item->iva0 == 0){
                    } else {
                        //$m = $item->precio * $item->descuento;
                        $total += ($item->precio) * $item ->cantidad * 0.16;
                    }
                }
                   //Sacamos el subtotal
                     $t = 0;
                        foreach($pro as $item){
                           // $m = $item->precio * $item->descuento;
                            $t += ($item->precio) * $item ->cantidad;
                        }
                $pdf = View::make('users/report',
                        compact(
                            'dpro',
                            'pro',
                            'domi',
                            'ped',
                            'pedido',
                            'total',
                            't',
                            'cli',
                            'extra'
                            ));

                 define('BUDGETS_DIR', public_path('uploads/pdf/cliente')); // I define this in a constants.php file
                    if (!is_dir(BUDGETS_DIR)){
                        mkdir(BUDGETS_DIR, 0755, true);
                    }
                    $outputName = str_random(10); // str_random is a [Laravel helper](http://laravel.com/docs/helpers#strings)
                    $pdfPath = BUDGETS_DIR.'/'.$outputName.'.pdf';
                    File::put($pdfPath, PDF::load($pdf, 'A4', 'portrait')->output());
                    Mail::send('emails/pdf', compact('pedido'), function($message) use ($pdfPath, $email, $num_ped){
                        $message->from('garden@live.com', 'Garden Central');
                        $message->to($email);
                        $message->subject('Tu pedido #'.$num_ped.' está en proceso.');
                        $message->attach($pdfPath);
                    });

        return Response::json($id);
 }


public function pedidoexistentedomicilio($id_d){

        $email = Auth::user()->email;
        $idusuario = Auth::user()->id;

        $cotizar = Input::get('cotizar');
        $formapago = Input::get('formapago');
        $msjeria = Input::get('msjeria');
        $r_extra = Input::get('r_extra');
        $total = Input::get('total');

        $idpro = json_decode(Input::get('aInfo'));
        $data_extra = json_decode(Input::get('nExtra'));

         $mensajeria = new Mensajeria;
         $mensajeria->id = Input::get('id');
         $mensajeria->nombre = $msjeria;
         $mensajeria->save();

        $resp = DB::table('cliente')
            ->where('usuario_id', $idusuario)
            ->pluck('id');

         $num_ped = date('Y').date('m').date("d").date("H").date("i").date("s");

        $clienteformapago = new ClienteFormaPago;
        $clienteformapago->cliente_id = $resp;
        $clienteformapago->forma_pago_id = $formapago;
        $clienteformapago->save();
        $mensajeria = new Mensajeria;
        $mensajeria->id = Input::get('id');
        $mensajeria->nombre = $msjeria;
        $mensajeria->save();

        $pedido = new Pedido;
        $pedido->cliente_id = $resp;
        $pedido->mensajeria_id = $mensajeria['id'];
        $pedido->direccion_cliente_id = $id_d;
        $pedido->forma_pago_id = $formapago;
        $pedido->num_pedido = $num_ped;
        $pedido->total = $total;
        $pedido->fecha_registro = date('Y-m-d');
        $pedido->anio_mes = date('Y-m');
        $pedido->anio = date('Y');
        $pedido->cotizar_envio = $cotizar;
        $pedido->extra_pedido = $r_extra;
        $pedido->observaciones =  " ";
        $pedido->save();

            //comprbamos si hay extras
            if($r_extra == 0){

            } else {
                for ($i=0; $i < count($data_extra); $i++) {

                    $extra = new ExtraPedido;
                    $extra->pedido_id = $pedido['id'];
                    $extra->clave = $data_extra[$i]->claveextra;
                    $extra->descripcion = $data_extra[$i]->contenido;
                    $extra->save();
                } //end for extras

            }

                for ($i=0; $i < count($idpro); $i++) {

                    //Insertamos en la tabla los datos temporales
                    $temporal = new TotalProducto;
                    $temporal->usuario_id = $idusuario;
                    $temporal->pedido_id = $pedido['id'];
                    $temporal->clave = $idpro[$i]->clave;
                    $temporal->cantidad = $idpro[$i]->cant;
                    $temporal->save();

                    //total producto
                    $total = DB::table('producto')
                            ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->pluck('cantidad');

                    //obtenemos el numero total de pedimentos
                    $pedimentos = DB::table('producto')
                            ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->count();

                    //Por cada pedimento del producto realizaremos un for
                    for($a = 0; $a < $pedimentos; $a++){
                        //obtenemos la cantidad del pedimento mas viejo
                        $cant = DB::table('producto')
                                ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                                ->where('producto.id', $idpro[$i]->idp)
                                ->orderBy('inventario_detalle.created_at', 'asc')
                                ->pluck('cantidad');

                        //obtenemos el pedimento mas viejo
                        $num_p = DB::table('producto')
                                ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                                ->where('producto.id', $idpro[$i]->idp)
                                ->orderBy('inventario_detalle.created_at', 'asc')
                                ->pluck('num_pedimento');

                        //obtenemos la cantidad almacenada temporalmente
                        $y1 = DB::table('total_producto')
                            ->where('usuario_id', $idusuario)
                            ->where('pedido_id', $pedido['id'])
                            ->where('clave', $idpro[$i]->clave)
                            ->pluck('cantidad');
                        //comparamos si la cantidad de productos elegida por el usuario es
                        //menor o igual a la cantidad del pedimento mas viejp
                        if($y1 <= $cant){
                         // echo " | Si es menor oh igual ";//obtenemos los id del producto
                        //Con el id del producto obtenemos el id del inventario
                        $inve1 = DB::table('inventario')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->pluck('id');
                        //descontamos la cantidad del inventario
                        $inv = Inventario::find($inve1);
                        $inv->cantidad -= $y1;
                        $inv->save();
                        //Obtenemos el id del inventario detalle
                        $id_i_d = DB::table('inventario_detalle')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('num_pedimento', $num_p)
                                ->orderBy('created_at', 'asc')
                                ->pluck('id');
                         //Consulta a la tabla precio
                        $pre_id = DB::table('producto_precio')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('tipo', $idpro[$i]->tipoprecio)
                                ->pluck('id');

                         //registramos en pedido detalle
                        $p_detalle = new PedidoDetalle;
                        $p_detalle->pedido_id = $pedido['id'];
                        $p_detalle->producto_id = $idpro[$i]->idp;
                        $p_detalle->producto_precio_id = $pre_id;
                        $p_detalle->precio = $idpro[$i]->preciop;
                        $p_detalle->num_pedimento = $num_p;
                        $p_detalle->cantidad = $y1;
                        $p_detalle->save();
                        //descontamos la cantidad del inventario_detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->cantidad -= $y1;
                        $inv_d->save();
                        //eliminamos los datos temporales
                        $idy = DB::table('total_producto')
                            ->where('usuario_id', $idusuario)
                            ->where('pedido_id', $pedido['id'])
                            ->where('clave', $idpro[$i]->clave)
                            ->pluck('id');
                        $y1 = TotalProducto::find($idy);
                        $y1->delete();
                        //comprobamos que no haya productos en el inventario detalle con cantidad de 0
                        $id_i_d = DB::table('inventario_detalle')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->where('num_pedimento', $num_p)
                                    ->where('cantidad', 0)
                                    ->pluck('id');
                       if(count($id_i_d ) == 0){
                        } else { //si hay
                            //Borramos el producto
                            $inv_d = InventarioDetalle::find($id_i_d);
                            $inv_d->delete();

                        }
                        //Alertas de los productos
                        $p = DB::table('producto')
                            ->orderBy('producto.cantidad_minima', 'asc')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->pluck('cantidad_minima');
                        $inventario = DB::table('inventario')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->pluck('cantidad');
                        //Comparamos la cantidad actual del producto del inventario con la cantidad minima del producto
                        if($inventario <= $p){
                            //verificamos si e producto ya existe
                            $i_v = DB::table('alertas')
                            ->where('producto_id', $idpro[$i]->idp)
                            ->first();
                            //Si no existe insertamos
                            if(count($i_v)==0){
                                $alerta = new Alerta;
                                $alerta->producto_id = $idpro[$i]->idp;
                                $alerta->estatus = 1;
                                $alerta->save();
                            //si ya existe no pasa nada
                            } else {

                            }


                        }
                        break; //Detenemos el ciclo -------------------------------------
                        } else { //si no es menor
                          //echo " | Es mayor, faltan productos";
                            //Con el id del producto obtenemos el id del inventario
                        $inve1 = DB::table('inventario')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->pluck('id');
                         //le restamos a la cantidad del producto del cliente la cantidad del pedimento
                         $y_m = $y1 - $cant;
                         //descontamos la cantidad del inventario
                        $inv = Inventario::find($inve1);
                        $inv->cantidad -= $cant;
                        $inv->save();
                        //---Cantidad del producto en el inventario
                        $t_i = DB::table('inventario')
                                ->where('id', $inve1)
                                ->pluck('cantidad');
                        //Obtenemos el id del inventario detalle
                        $id_i_d = DB::table('inventario_detalle')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('num_pedimento', $num_p)
                                ->orderBy('created_at', 'asc')
                                ->pluck('id');
                        //descontamos la cantidad del inventario_detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->cantidad -= $y_m;
                        $inv_d->save();
                        //Consulta a la tabla precio
                        $pre_id = DB::table('producto_precio')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('tipo', $idpro[$i]->tipoprecio)
                                ->pluck('id');
                        //Registramos en el pedido detalle
                        $p_detalle = new PedidoDetalle;
                        $p_detalle->pedido_id = $pedido['id'];
                        $p_detalle->producto_id = $idpro[$i]->idp;
                        $p_detalle->producto_precio_id = $pre_id;
                        $p_detalle->precio = $idpro[$i]->preciop;
                        $p_detalle->num_pedimento = $num_p;
                        $p_detalle->cantidad = $cant;
                        $p_detalle->save();
                        //Borramos el producto del inventario detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->delete();
                        //Actualizamos la cantidad de los datos temporales
                        $id_t = DB::table('total_producto')
                                ->where('usuario_id', $idusuario)
                                ->where('pedido_id', $pedido['id'])
                                ->where('clave', $idpro[$i]->clave)
                                ->pluck('id');

                        $y_a = TotalProducto::find($id_t);
                        $y_a->cantidad -= $cant;
                        $y_a->save();
                        $cant_a = DB::table('total_producto')
                                ->where('usuario_id', $idusuario)
                                ->where('pedido_id', $pedido['id'])
                                ->where('clave', $idpro[$i]->clave)
                                ->pluck('cantidad');
                        } //else si es mayor
                    } //END FOR PEDIMENTOS
            } //end for principal

        $id = $pedido['id'];
            $iddirec = DB::table('pedido')
                    ->join('direccion_cliente','pedido.direccion_cliente_id', '=','direccion_cliente.id')
                    ->where('pedido.id', $id)
                    ->pluck('pedido.direccion_cliente_id');
            $pedido = DB::table('pedido')
                        ->where('pedido.id', $id)
                        ->get();
                 $cli = DB::table('cliente')
                    ->join('pedido', 'cliente.id', '=', 'pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                 $domi = DB::table('direccion_cliente')
                    ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
                    ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
                    ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
                    ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
                    ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
                    ->where("direccion_cliente.id", $iddirec)
                    ->get();
                 $ped = DB::table('cliente')
                    ->join('pedido','cliente.id', '=','pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                  $pro = DB::table('producto')
                              ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                                ->where('pedido_detalle.pedido_id', $id)
                                ->select('clave', 'nombre', 'color', 'pedido_detalle.precio','iva0', 'cantidad', 'num_pedimento')
                                ->get();
                $extra = DB::table('extra_pedido')
                        ->where('pedido_id', $id)
                        ->get();

                $dpro = DB::table('pedido_detalle')
                            ->join('producto','pedido_detalle.producto_id', '=','producto.id')
                            ->where('pedido_detalle.id', $id)
                            ->get();
                   //Sacamos el iva
                    $total = 0;
                    foreach($pro as $item){
                    if($item->iva0 == 0){
                    } else {
                        //$m = $item->precio * $item->descuento;
                        $total += ($item->precio) * $item ->cantidad * 0.16;
                    }
                }
                   //Sacamos el subtotal
                     $t = 0;
                        foreach($pro as $item){
                           // $m = $item->precio * $item->descuento;
                            $t += ($item->precio) * $item ->cantidad;
                        }
                $pdf = View::make('users/report',
                        compact(
                            'dpro',
                            'pro',
                            'domi',
                            'ped',
                            'pedido',
                            'total',
                            't',
                            'cli',
                            'extra'
                            ));

                 define('BUDGETS_DIR', public_path('uploads/pdf/cliente')); // I define this in a constants.php file
                    if (!is_dir(BUDGETS_DIR)){
                        mkdir(BUDGETS_DIR, 0755, true);
                    }
                    $outputName = str_random(10); // str_random is a [Laravel helper](http://laravel.com/docs/helpers#strings)
                    $pdfPath = BUDGETS_DIR.'/'.$outputName.'.pdf';
                    File::put($pdfPath, PDF::load($pdf, 'A4', 'portrait')->output());
                    Mail::send('emails/pdf', compact('pedido'), function($message) use ($pdfPath, $email, $num_ped){
                        $message->from('garden@live.com', 'Garden Central');
                        $message->to($email);
                        $message->subject('Tu pedido #'.$num_ped.' está en proceso.');
                        $message->attach($pdfPath);
                    });

        return Response::json($id);

}





 public function nuevopedido($id){
        $idusuario = Auth::user()->id;
        $email = Auth::user()->email;
        $resp = DB::table('cliente')
            ->where('usuario_id', $idusuario)->pluck('id');
        //Recibimos el Array y lo decodificamos desde json, para poder utilizarlo como objeto
        $idpro = json_decode(Input::get('aInfo'));
        $data_extra = json_decode(Input::get('nExtra'));
        $cotizar = Input::get('cotizar');
        $pais = Input::get('pais');
        $estado = Input::get('estado');
        $municipio = Input::get('municipio');
        $calle1 = Input::get('calle1');
        $calle2 = Input::get('calle2');
        $colonia = Input::get('colonia');
        $delegacion = Input::get('delegacion');
        $cp = Input::get('cp');
        $tipodom = Input::get('tipodom');
        $tel = Input::get('tel');
        $tipotel = Input::get('tipotel');
        $formapago = Input::get('formapago');
        $msjeria = Input::get('msjeria');
        $coment = Input::get('coment');
        $r_extra = Input::get('r_extra');
        $total = Input::get('total');
          $mensajeria = new Mensajeria;
          $mensajeria->id = Input::get('id');
          $mensajeria->nombre = $msjeria;
          $mensajeria->save();
         $num_ped = date('Y').date('m').date("d").date("H").date("i").date("s");

    if (Request::ajax()) {
        if($id == 0){
            $telefono = new TelefonoCliente;
            $telefono->id = Input::get('id');
            $telefono->cliente_id = $resp;
            $telefono->numero = $tel;
            $telefono->tipo_tel = $tipotel;
            $telefono->estatus = "1";
            $telefono->save();

            $direccion = new DireccionCliente();
            $direccion->id = Input::get('id');
            $direccion->cliente_id = $resp;
            $direccion->pais_id = $pais;
            $direccion->estado_id = $estado;
            $direccion->municipio_id = $municipio;
            $direccion->telefono_cliente_id = $telefono['id'];
            $direccion->calle1 = $calle1;
            $direccion->calle2 = $calle2;
            $direccion->colonia = $colonia;
            $direccion->delegacion = $delegacion;
            $direccion->codigo_postal = $cp;
            $direccion->tipo =  $tipodom;
            $direccion->estatus = "1";
            $direccion->save();

            $clienteformapago = new ClienteFormaPago;
            $clienteformapago->cliente_id = $resp;
            $clienteformapago->forma_pago_id = $formapago;
            $clienteformapago->save();

            $pedido = new Pedido;
            //$pedido->id = Input::get('id');
            $pedido->cliente_id = $resp;
            $pedido->mensajeria_id = $mensajeria['id'];
            $pedido->direccion_cliente_id = $direccion['id'];
            $pedido->forma_pago_id = $formapago;
            $pedido->num_pedido = $num_ped;
            $pedido->total = $total;
            $pedido->fecha_registro = date('Y-m-d');
            $pedido->anio_mes = date('Y-m');
            $pedido->anio = date('Y');
            $pedido->cotizar_envio = $cotizar;
            $pedido->extra_pedido = $r_extra;
            $pedido->observaciones =  $coment;
            $pedido->save();

               //comprbamos si hay extras
            if($r_extra == 0){

            } else {
                for ($i=0; $i < count($data_extra); $i++) {

                    $extra = new ExtraPedido;
                    $extra->pedido_id = $pedido['id'];
                    $extra->clave = $data_extra[$i]->claveextra;
                    $extra->descripcion = $data_extra[$i]->contenido;
                    $extra->save();
                } //end for extras

            }


                for ($i=0; $i < count($idpro); $i++) {

                    //Insertamos en la tabla los datos temporales
                    $temporal = new TotalProducto;
                    $temporal->usuario_id = $idusuario;
                    $temporal->pedido_id = $pedido['id'];
                    $temporal->clave = $idpro[$i]->clave;
                    $temporal->cantidad = $idpro[$i]->cant;
                    $temporal->save();

                    //total producto
                    $total = DB::table('producto')
                            ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->pluck('cantidad');

                    //obtenemos el numero total de pedimentos
                    $pedimentos = DB::table('producto')
                            ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->count();

                    //Por cada pedimento del producto realizaremos un for
                    for($a = 0; $a < $pedimentos; $a++){
                        //obtenemos la cantidad del pedimento mas viejo
                        $cant = DB::table('producto')
                                ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                                ->where('producto.id', $idpro[$i]->idp)
                                ->orderBy('inventario_detalle.created_at', 'asc')
                                ->pluck('cantidad');

                        //obtenemos el pedimento mas viejo
                        $num_p = DB::table('producto')
                                ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                                ->where('producto.id', $idpro[$i]->idp)
                                ->orderBy('inventario_detalle.created_at', 'asc')
                                ->pluck('num_pedimento');

                        //obtenemos la cantidad almacenada temporalmente
                        $y1 = DB::table('total_producto')
                            ->where('usuario_id', $idusuario)
                            ->where('pedido_id', $pedido['id'])
                            ->where('clave', $idpro[$i]->clave)
                            ->pluck('cantidad');

                        //comparamos si la cantidad de productos elegida por el usuario es
                        //menor o igual a la cantidad del pedimento mas viejp
                        if($y1 <= $cant){
                         // echo " | Si es menor oh igual ";//obtenemos los id del producto
                        //Con el id del producto obtenemos el id del inventario
                        $inve1 = DB::table('inventario')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->pluck('id');
                        //descontamos la cantidad del inventario
                        $inv = Inventario::find($inve1);
                        $inv->cantidad -= $y1;
                        $inv->save();
                        //Obtenemos el id del inventario detalle
                        $id_i_d = DB::table('inventario_detalle')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('num_pedimento', $num_p)
                                ->orderBy('created_at', 'asc')
                                ->pluck('id');
                         //Consulta a la tabla precio
                        $pre_id = DB::table('producto_precio')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('tipo', $idpro[$i]->tipoprecio)
                                ->pluck('id');

                         //registramos en pedido detalle
                        $p_detalle = new PedidoDetalle;
                        $p_detalle->pedido_id = $pedido['id'];
                        $p_detalle->producto_id = $idpro[$i]->idp;
                        $p_detalle->producto_precio_id = $pre_id;
                        $p_detalle->precio = $idpro[$i]->preciop;
                        $p_detalle->num_pedimento = $num_p;
                        $p_detalle->cantidad = $y1;
                        $p_detalle->save();

                        //descontamos la cantidad del inventario_detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->cantidad -= $y1;
                        $inv_d->save();
                        //eliminamos los datos temporales
                        $idy = DB::table('total_producto')
                            ->where('usuario_id', $idusuario)
                            ->where('pedido_id', $pedido['id'])
                            ->where('clave', $idpro[$i]->clave)
                            ->pluck('id');
                        $y1 = TotalProducto::find($idy);
                        $y1->delete();

                        //comprobamos que no haya productos en el inventario detalle con cantidad de 0
                        $id_i_d = DB::table('inventario_detalle')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->where('num_pedimento', $num_p)
                                    ->where('cantidad', 0)
                                    ->pluck('id');

                       if(count($id_i_d ) == 0){
                        } else { //si hay
                            //Borramos el producto
                            $inv_d = InventarioDetalle::find($id_i_d);
                            $inv_d->delete();

                        }

                        //Alertas de los productos
                        $p = DB::table('producto')
                            ->orderBy('producto.cantidad_minima', 'asc')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->pluck('cantidad_minima');
                        $inventario = DB::table('inventario')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->pluck('cantidad');

                        //Comparamos la cantidad actual del producto del inventario con la cantidad minima del producto
                        if($inventario <= $p){
                            //verificamos si e producto ya existe
                            $i_v = DB::table('alertas')
                            ->where('producto_id', $idpro[$i]->idp)
                            ->first();
                            //Si no existe insertamos
                            if(count($i_v)==0){
                                $alerta = new Alerta;
                                $alerta->producto_id = $idpro[$i]->idp;
                                $alerta->estatus = 1;
                                $alerta->save();
                            //si ya existe no pasa nada
                            } else {

                            }


                        }
                        break; //Detenemos el ciclo -------------------------------------
                        } else { //si no es menor
                          //echo " | Es mayor, faltan productos";
                            //Con el id del producto obtenemos el id del inventario
                        $inve1 = DB::table('inventario')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->pluck('id');
                         //le restamos a la cantidad del producto del cliente la cantidad del pedimento
                         $y_m = $y1 - $cant;
                         //descontamos la cantidad del inventario
                        $inv = Inventario::find($inve1);
                        $inv->cantidad -= $cant;
                        $inv->save();
                        //---Cantidad del producto en el inventario
                        $t_i = DB::table('inventario')
                                ->where('id', $inve1)
                                ->pluck('cantidad');

                        //Obtenemos el id del inventario detalle
                        $id_i_d = DB::table('inventario_detalle')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('num_pedimento', $num_p)
                                ->orderBy('created_at', 'asc')
                                ->pluck('id');

                        //descontamos la cantidad del inventario_detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->cantidad -= $y_m;
                        $inv_d->save();
                        //Consulta a la tabla precio
                        $pre_id = DB::table('producto_precio')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('tipo', $idpro[$i]->tipoprecio)
                                ->pluck('id');
                        //Registramos en el pedido detalle
                        $p_detalle = new PedidoDetalle;
                        $p_detalle->pedido_id = $pedido['id'];
                        $p_detalle->producto_id = $idpro[$i]->idp;
                        $p_detalle->producto_precio_id = $pre_id;
                        $p_detalle->precio = $idpro[$i]->preciop;
                        $p_detalle->num_pedimento = $num_p;
                        $p_detalle->cantidad = $cant;
                        $p_detalle->save();

                        //Borramos el producto del inventario detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->delete();
                        //Actualizamos la cantidad de los datos temporales
                        $id_t = DB::table('total_producto')
                                ->where('usuario_id', $idusuario)
                                ->where('pedido_id', $pedido['id'])
                                ->where('clave', $idpro[$i]->clave)
                                ->pluck('id');

                        $y_a = TotalProducto::find($id_t);
                        $y_a->cantidad -= $cant;
                        $y_a->save();
                        $cant_a = DB::table('total_producto')
                                ->where('usuario_id', $idusuario)
                                ->where('pedido_id', $pedido['id'])
                                ->where('clave', $idpro[$i]->clave)
                                ->pluck('cantidad');

                        } //else si es mayor
                    } //END FOR PEDIMENTOS
            } //end for principal
        } else {
            $direccion = new DireccionCliente();
            $direccion->id = Input::get('id');
            $direccion->cliente_id = $resp;
            $direccion->pais_id = $pais;
            $direccion->estado_id = $estado;
            $direccion->municipio_id = $municipio;
            $direccion->telefono_cliente_id = $id;
            $direccion->calle1 = $calle1;
            $direccion->calle2 = $calle2;
            $direccion->colonia = $colonia;
            $direccion->delegacion = $delegacion;
            $direccion->codigo_postal = $cp;
            $direccion->tipo =  $tipodom;
            $direccion->estatus = "1";
            $direccion->save();
            $clienteformapago = new ClienteFormaPago;
            $clienteformapago->cliente_id = $resp;
            $clienteformapago->forma_pago_id = $formapago;
            $clienteformapago->save();
            $mensajeria = new Mensajeria;
            $mensajeria->id = Input::get('id');
            $mensajeria->nombre = $msjeria;
            $mensajeria->save();

            $pedido = new Pedido;
            $pedido->cliente_id = $resp;
            $pedido->mensajeria_id = $mensajeria['id'];
            $pedido->direccion_cliente_id = $direccion['id'];
            $pedido->forma_pago_id = $formapago;
            $pedido->num_pedido = $num_ped;
            $pedido->total = $total;
            $pedido->fecha_registro = date('Y-m-d');
            $pedido->anio_mes = date('Y-m');
            $pedido->anio = date('Y');
            $pedido->cotizar_envio = $cotizar;
            $pedido->extra_pedido = $r_extra;
            $pedido->observaciones =  $coment;
            $pedido->save();

              //comprbamos si hay extras
            if($r_extra == 0){

            } else {
                for ($i=0; $i < count($data_extra); $i++) {

                    $extra = new ExtraPedido;
                    $extra->pedido_id = $pedido['id'];
                    $extra->clave = $data_extra[$i]->claveextra;
                    $extra->descripcion = $data_extra[$i]->contenido;
                    $extra->save();
                } //end for extras

            }

                for ($i=0; $i < count($idpro); $i++) {

                    //Insertamos en la tabla los datos temporales
                    $temporal = new TotalProducto;
                    $temporal->usuario_id = $idusuario;
                    $temporal->pedido_id = $pedido['id'];
                    $temporal->clave = $idpro[$i]->clave;
                    $temporal->cantidad = $idpro[$i]->cant;
                    $temporal->save();
                    //total producto
                    $total = DB::table('producto')
                            ->join('inventario', 'producto.id', '=', 'inventario.producto_id')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->pluck('cantidad');
                    //obtenemos el numero total de pedimentos
                    $pedimentos = DB::table('producto')
                            ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->count();
                    //Por cada pedimento del producto realizaremos un for
                    for($a = 0; $a < $pedimentos; $a++){
                        //obtenemos la cantidad del pedimento mas viejo
                        $cant = DB::table('producto')
                                ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                                ->where('producto.id', $idpro[$i]->idp)
                                ->orderBy('inventario_detalle.created_at', 'asc')
                                ->pluck('cantidad');

                        //obtenemos el pedimento mas viejo
                        $num_p = DB::table('producto')
                                ->join('inventario_detalle', 'producto.id', '=', 'inventario_detalle.producto_id')
                                ->where('producto.id', $idpro[$i]->idp)
                                ->orderBy('inventario_detalle.created_at', 'asc')
                                ->pluck('num_pedimento');
                        //obtenemos la cantidad almacenada temporalmente
                        $y1 = DB::table('total_producto')
                            ->where('usuario_id', $idusuario)
                            ->where('pedido_id', $pedido['id'])
                            ->where('clave', $idpro[$i]->clave)
                            ->pluck('cantidad');
                        //comparamos si la cantidad de productos elegida por el usuario es
                        //menor o igual a la cantidad del pedimento mas viejp
                        if($y1 <= $cant){
                         // echo " | Si es menor oh igual ";//obtenemos los id del producto
                        //Con el id del producto obtenemos el id del inventario
                        $inve1 = DB::table('inventario')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->pluck('id');
                        //descontamos la cantidad del inventario
                        $inv = Inventario::find($inve1);
                        $inv->cantidad -= $y1;
                        $inv->save();
                        //Obtenemos el id del inventario detalle
                        $id_i_d = DB::table('inventario_detalle')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('num_pedimento', $num_p)
                                ->orderBy('created_at', 'asc')
                                ->pluck('id');
                         //Consulta a la tabla precio
                        $pre_id = DB::table('producto_precio')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('tipo', $idpro[$i]->tipoprecio)
                                ->pluck('id');
                         //registramos en pedido detalle
                        $p_detalle = new PedidoDetalle;
                        $p_detalle->pedido_id = $pedido['id'];
                        $p_detalle->producto_id = $idpro[$i]->idp;
                        $p_detalle->producto_precio_id = $pre_id;
                        $p_detalle->precio = $idpro[$i]->preciop;
                        $p_detalle->num_pedimento = $num_p;
                        $p_detalle->cantidad = $y1;
                        $p_detalle->save();
                        //descontamos la cantidad del inventario_detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->cantidad -= $y1;
                        $inv_d->save();
                        //eliminamos los datos temporales
                        $idy = DB::table('total_producto')
                            ->where('usuario_id', $idusuario)
                            ->where('pedido_id', $pedido['id'])
                            ->where('clave', $idpro[$i]->clave)
                            ->pluck('id');
                        $y1 = TotalProducto::find($idy);
                        $y1->delete();
                        //comprobamos que no haya productos en el inventario detalle con cantidad de 0
                        $id_i_d = DB::table('inventario_detalle')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->where('num_pedimento', $num_p)
                                    ->where('cantidad', 0)
                                    ->pluck('id');
                       if(count($id_i_d ) == 0){
                        } else { //si hay
                            //Borramos el producto
                            $inv_d = InventarioDetalle::find($id_i_d);
                            $inv_d->delete();

                        }
                        //Alertas de los productos
                        $p = DB::table('producto')
                            ->orderBy('producto.cantidad_minima', 'asc')
                            ->where('producto.id', $idpro[$i]->idp)
                            ->pluck('cantidad_minima');
                        $inventario = DB::table('inventario')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->pluck('cantidad');
                        //Comparamos la cantidad actual del producto del inventario con la cantidad minima del producto
                        if($inventario <= $p){
                            //verificamos si e producto ya existe
                            $i_v = DB::table('alertas')
                            ->where('producto_id', $idpro[$i]->idp)
                            ->first();
                            //Si no existe insertamos
                            if(count($i_v)==0){
                                $alerta = new Alerta;
                                $alerta->producto_id = $idpro[$i]->idp;
                                $alerta->estatus = 1;
                                $alerta->save();
                            //si ya existe no pasa nada
                            } else {

                            }


                        }
                        break; //Detenemos el ciclo -------------------------------------
                        } else { //si no es menor
                          //echo " | Es mayor, faltan productos";
                            //Con el id del producto obtenemos el id del inventario
                        $inve1 = DB::table('inventario')
                                    ->where('producto_id', $idpro[$i]->idp)
                                    ->pluck('id');
                         //le restamos a la cantidad del producto del cliente la cantidad del pedimento
                         $y_m = $y1 - $cant;
                         //descontamos la cantidad del inventario
                        $inv = Inventario::find($inve1);
                        $inv->cantidad -= $cant;
                        $inv->save();
                        //---Cantidad del producto en el inventario
                        $t_i = DB::table('inventario')
                                ->where('id', $inve1)
                                ->pluck('cantidad');
                        //Obtenemos el id del inventario detalle
                        $id_i_d = DB::table('inventario_detalle')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('num_pedimento', $num_p)
                                ->orderBy('created_at', 'asc')
                                ->pluck('id');
                        //descontamos la cantidad del inventario_detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->cantidad -= $y_m;
                        $inv_d->save();
                        //Consulta a la tabla precio
                        $pre_id = DB::table('producto_precio')
                                ->where('producto_id', $idpro[$i]->idp)
                                ->where('tipo', $idpro[$i]->tipoprecio)
                                ->pluck('id');
                        //Registramos en el pedido detalle
                        $p_detalle = new PedidoDetalle;
                        $p_detalle->pedido_id = $pedido['id'];
                        $p_detalle->producto_id = $idpro[$i]->idp;
                        $p_detalle->producto_precio_id = $pre_id;
                        $p_detalle->precio = $idpro[$i]->preciop;
                        $p_detalle->num_pedimento = $num_p;
                        $p_detalle->cantidad = $cant;
                        $p_detalle->save();
                        //Borramos el producto del inventario detalle
                        $inv_d = InventarioDetalle::find($id_i_d);
                        $inv_d->delete();
                        //Actualizamos la cantidad de los datos temporales
                        $id_t = DB::table('total_producto')
                                ->where('usuario_id', $idusuario)
                                ->where('pedido_id', $pedido['id'])
                                ->where('clave', $idpro[$i]->clave)
                                ->pluck('id');

                        $y_a = TotalProducto::find($id_t);
                        $y_a->cantidad -= $cant;
                        $y_a->save();
                        $cant_a = DB::table('total_producto')
                                ->where('usuario_id', $idusuario)
                                ->where('pedido_id', $pedido['id'])
                                ->where('clave', $idpro[$i]->clave)
                                ->pluck('cantidad');
                        } //else si es mayor
                    } //END FOR PEDIMENTOS
            } //end for principal
        }
            $id = $pedido['id'];
            $iddirec = DB::table('pedido')
                    ->join('direccion_cliente','pedido.direccion_cliente_id', '=','direccion_cliente.id')
                    ->where('pedido.id', $id)
                    ->pluck('pedido.direccion_cliente_id');
            $pedido = DB::table('pedido')
                        ->where('pedido.id', $id)
                        ->get();
                 $cli = DB::table('cliente')
                    ->join('pedido', 'cliente.id', '=', 'pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                 $domi = DB::table('direccion_cliente')
                    ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
                    ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
                    ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
                    ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
                    ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
                    ->where("direccion_cliente.id", $iddirec)
                    ->get();
                 $ped = DB::table('cliente')
                    ->join('pedido','cliente.id', '=','pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                  $pro = DB::table('producto')
                              ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                                ->where('pedido_detalle.pedido_id', $id)
                                ->select('clave', 'nombre', 'color', 'pedido_detalle.precio','iva0', 'cantidad', 'num_pedimento')
                                ->get();
                $extra = DB::table('extra_pedido')
                        ->where('pedido_id', $id)
                        ->get();

                $dpro = DB::table('pedido_detalle')
                            ->join('producto','pedido_detalle.producto_id', '=','producto.id')
                            ->where('pedido_detalle.id', $id)
                            ->get();

                   //Sacamos el iva
                    $total = 0;
                    foreach($pro as $item){
                    if($item->iva0 == 0){
                    } else {
                        //$m = $item->precio * $item->descuento;
                        $total += ($item->precio) * $item ->cantidad * 0.16;
                    }
                }
                   //Sacamos el subtotal
                     $t = 0;
                        foreach($pro as $item){
                           // $m = $item->precio * $item->descuento;
                            $t += ($item->precio) * $item ->cantidad;
                        }
                $pdf = View::make('users/report',
                        compact(
                            'dpro',
                            'pro',
                            'domi',
                            'ped',
                            'pedido',
                            'total',
                            't',
                            'cli',
                            'extra'
                            ));
                 define('BUDGETS_DIR', public_path('uploads/pdf/cliente')); // I define this in a constants.php file
                    if (!is_dir(BUDGETS_DIR)){
                        mkdir(BUDGETS_DIR, 0755, true);
                    }
                    $outputName = str_random(10); // str_random is a [Laravel helper](http://laravel.com/docs/helpers#strings)
                    $pdfPath = BUDGETS_DIR.'/'.$outputName.'.pdf';
                    File::put($pdfPath, PDF::load($pdf, 'A4', 'portrait')->output());
                    Mail::send('emails/pdf', compact('pedido'), function($message) use ($pdfPath, $email, $num_ped){
                        $message->from('garden@live.com', 'Garden Central');
                        $message->to($email);
                        $message->subject('Tu pedido #'.$num_ped.' está en proceso.');
                        $message->attach($pdfPath);
                    });
    return Response::json($id);
  }

 }



 public function enviaragente($id){
            $iddirec = DB::table('pedido')
                    ->join('direccion_cliente','pedido.direccion_cliente_id', '=','direccion_cliente.id')
                    ->where('pedido.id', $id)
                    ->pluck('pedido.direccion_cliente_id');
            $pedido = DB::table('pedido')
                        ->where('pedido.id', $id)
                        ->get();
                 $cli = DB::table('cliente')
                    ->join('pedido', 'cliente.id', '=', 'pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                 $domi = DB::table('direccion_cliente')
                    ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
                    ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
                    ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
                    ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
                    ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
                    ->where("direccion_cliente.id", $iddirec)
                    ->get();
                 $ped = DB::table('cliente')
                    ->join('pedido','cliente.id', '=','pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                  $pro = DB::table('producto')
                              ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                                ->where('pedido_detalle.pedido_id', $id)
                                ->select('clave', 'nombre', 'color', 'pedido_detalle.precio','iva0', 'cantidad', 'num_pedimento')
                                ->get();
                $extra = DB::table('extra_pedido')
                        ->where('pedido_id', $id)
                        ->get();

                $dpro = DB::table('pedido_detalle')
                            ->join('producto','pedido_detalle.producto_id', '=','producto.id')
                            ->where('pedido_detalle.id', $id)
                            ->get();
                $pdf = View::make('users/report2',
                        compact(
                            'dpro',
                            'pro',
                            'domi',
                            'ped',
                            'pedido',
                            'cli',
                            'extra'
                            ));
                $admin = DB::table('usuario')
                        ->where('rol_id', 3)
                        ->pluck('email');

                 define('BUDGETS_DIR', public_path('uploads/pdf/agente')); // I define this in a constants.php file
                    if (!is_dir(BUDGETS_DIR)){
                        mkdir(BUDGETS_DIR, 0755, true);
                    }
                    $outputName = str_random(10); // str_random is a [Laravel helper](http://laravel.com/docs/helpers#strings)
                    $pdfPath = BUDGETS_DIR.'/'.$outputName.'.pdf';
                    File::put($pdfPath, PDF::load($pdf, 'A4', 'portrait')->output());
                   $n_ped = DB::table('pedido')
                      ->where('pedido.id', $id)
                      ->pluck('num_pedido');
                    Mail::send('emails/pdf', compact('pedido'), function($message) use ($pdfPath, $admin, $n_ped){
                        $message->from('garden@live.com', 'Garden Central');
                        $message->to($admin);
                        $message->subject('Nuevo pedido #'.$n_ped);
                        $message->attach($pdfPath);
                    });
                    return Response::json($id);
 }


 public function enviaremail($id){
            $email = Input::get('e');
            $asunto = Input::get('asunto');
            $iddirec = DB::table('pedido')
                    ->join('direccion_cliente','pedido.direccion_cliente_id', '=','direccion_cliente.id')
                    ->where('pedido.id', $id)
                    ->pluck('pedido.direccion_cliente_id');
            $pedido = DB::table('pedido')
                        ->where('pedido.id', $id)
                        ->get();
                 $cli = DB::table('cliente')
                    ->join('pedido', 'cliente.id', '=', 'pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                 $domi = DB::table('direccion_cliente')
                    ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
                    ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
                    ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
                    ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
                    ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
                    ->where("direccion_cliente.id", $iddirec)
                    ->get();
                 $ped = DB::table('cliente')
                    ->join('pedido','cliente.id', '=','pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                  $pro = DB::table('producto')
                              ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                                ->where('pedido_detalle.pedido_id', $id)
                                ->select('clave', 'nombre', 'color', 'pedido_detalle.precio','iva0', 'cantidad', 'num_pedimento')
                                ->get();
                $extra = DB::table('extra_pedido')
                        ->where('pedido_id', $id)
                        ->get();

                $dpro = DB::table('pedido_detalle')
                            ->join('producto','pedido_detalle.producto_id', '=','producto.id')
                            ->where('pedido_detalle.id', $id)
                            ->get();
                   //Sacamos el iva
                    $total = 0;
                    foreach($pro as $item){
                    if($item->iva0 == 0){
                    } else {
                        //$m = $item->precio * $item->descuento;
                        $total += ($item->precio) * $item ->cantidad * 0.16;
                    }
                }
                   //Sacamos el subtotal
                     $t = 0;
                        foreach($pro as $item){
                           // $m = $item->precio * $item->descuento;
                            $t += ($item->precio) * $item ->cantidad;
                        }
                $pdf = View::make('users/report',
                        compact(
                            'dpro',
                            'pro',
                            'domi',
                            'ped',
                            'pedido',
                            'total',
                            't',
                            'cli',
                            'extra'
                            ));
                 define('BUDGETS_DIR', public_path('uploads/pdf/cliente')); // I define this in a constants.php file
                    if (!is_dir(BUDGETS_DIR)){
                        mkdir(BUDGETS_DIR, 0755, true);
                    }
                    $outputName = str_random(10); // str_random is a [Laravel helper](http://laravel.com/docs/helpers#strings)
                    $pdfPath = BUDGETS_DIR.'/'.$outputName.'.pdf';
                    File::put($pdfPath, PDF::load($pdf, 'A4', 'portrait')->output());
                    Mail::send('emails/pdf', compact('pedido'), function($message) use ($pdfPath, $email, $asunto){
                        //$e = 'luis_mh@outlook.es';
                        $message->from('garden@live.com', 'Garden Central');
                        $message->to($email);
                        $message->subject($asunto);
                        $message->attach($pdfPath);
                    });
                    //return Response::json('Hola');

   }


     //Editar domicilio
    public function editar($uddom){
       // return Response::json($iddom);
       //
       $estado = DB::table('estado')
              ->select('id','estados')->get();
        $idmuni = DB::table('direccion_cliente')
            ->where("id", $uddom)->pluck('municipio_id');
        $muni = DB::table('municipio')
            ->select('id')
            ->where("id", $idmuni)->get();
        $domi = DB::table('direccion_cliente')
            ->join('pedido', 'direccion_cliente.id', '=', 'pedido.direccion_cliente_id')
            ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
            ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
            ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
            ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
            ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
            ->select('estado.id', 'pais', 'estados', 'municipio', 'calle1', 'calle2', 'colonia', 'delegacion', 'codigo_postal', 'numero', 'tipo_tel')
            ->where("direccion_cliente.id", $uddom)->first();
        return Response::json(array('estado' => $estado, $domi, 'muni' => $muni));
    }
    public function estado($id){
        $estado = DB::table('estado')
                ->where('id', $id)->pluck('id');
        $municipio = DB::table('municipio')
                    ->select('id','municipio')
                    ->where('estado_id', $estado)->get();
        return Response::json(array('municipio' => $municipio));
    }
    //Actualiar el domiclio
    public function actualizar(){
        //Recivimos los datos mandados
        $id = Input::get('id');
        $pais = Input::get('pais');
        $estado = Input::get('estado');
        $municipio = Input::get('municipio');
        $calle1 = Input::get('calle1');
        $calle2 = Input::get('calle2');
        $colonia = Input::get('colonia');
        $delegacion = Input::get('delegacion');
        $cp = Input::get('cp');
        $tel = Input::get('tel');
        $tipotel = Input::get('tipotel');
        $tipodom = Input::get('tipodom');
        //Consultas
        $consulta = DB::table('direccion_cliente')
                ->where('id', $id)->pluck('telefono_cliente_id');
        $idtel = DB::table('telefono_cliente')
                    ->where('id', $consulta)->pluck('id');
         $idusuario = Auth::user()->id;
        $resp = DB::table('cliente')
            ->where('usuario_id', $idusuario)->pluck('id');
            //Actualizamos el telefono
            $telefono = TelefonoCliente::find($idtel);
            $telefono->cliente_id = $resp;
            $telefono->numero = $tel;
            $telefono->tipo_tel = $tipotel;
            $telefono->estatus = "1";
            $telefono->save();
            //Actualizamos la direccion
            $direccion = DireccionCliente::find($id);
            $direccion->cliente_id = $resp;
            $direccion->pais_id = $pais;
            $direccion->estado_id = $estado;
            $direccion->municipio_id = $municipio;
            $direccion->telefono_cliente_id = $idtel;
            $direccion->calle1 = $calle1;
            $direccion->calle2 = $calle2;
            $direccion->colonia = $colonia;
            $direccion->delegacion = $delegacion;
            $direccion->codigo_postal = $cp;
            $direccion->tipo =  $tipodom;
            $direccion->estatus = "1";
            $direccion->save();
            //Mandamos los datos actualizados
            $domi = DB::table('direccion_cliente')
            ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
            ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
            ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
            ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
            ->select('direccion_cliente.id', 'pais', 'estados', 'municipio', 'calle1', 'calle2', 'colonia', 'delegacion', 'codigo_postal','tipo' ,'numero')
            ->where("direccion_cliente.id", $id)->first();
           return Response::json($domi);
    }
    //detalle del producto
public function datosdelpedido($iddom){
    if (Auth::check()) {
       $userid = Auth::user()->id;
       $idcliente = DB::table('cliente')
            ->where("usuario_id", $userid)
            ->pluck('id');
        $iddi = DB::table('pedido')
             ->join('direccion_cliente', 'pedido.direccion_cliente_id', '=', 'direccion_cliente.id')
             ->select('direccion_cliente.id')
            ->where('pedido.id', $iddom)
            ->pluck('direccion_cliente.id');

         $idp = DB::table('pedido')
                    ->where('direccion_cliente_id', $iddi)
                    ->pluck('cliente_id');

            if($iddi == NULL){
              $pedido = DB::table('pedido')
                 ->select('pedido.created_at','num_pedido')
                 ->where('pedido.id', $iddom)
                 ->take(1)
                 ->get();
              $direc = DB::table('direccion_cliente')
                ->join('pedido', 'direccion_cliente.id', '=', 'pedido.direccion_cliente_id')
                ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
                ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
                ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
                ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
                ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
                ->where("direccion_cliente.id", $iddi)
                ->take(1)
                ->get();
            $cli = DB::table('cliente')
                    ->where('id', $idcliente)
                    ->get();
            $producto = DB::table('producto')
                      ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                        ->where('pedido_detalle.pedido_id', $iddom)
                        ->select('clave', 'nombre', 'color', 'pedido_detalle.precio','iva0', 'cantidad', 'num_pedimento')
                        ->get();
            $extra = DB::table('extra_pedido')
                ->where('pedido_id', $iddom)
                ->get();
           // if(count(\Session::get('cart')) <= 0) return Redirect::to('users');
            //subtotal
            $total = 0;
            foreach($producto as $item){
               // $m = $item->precio * $item->descuento;
                $total += ($item->precio) * $item ->cantidad;
            }
               //iva
                $t = 0;
                    foreach($producto as $item){
                    if($item->iva0 == 0){
                    } else {
                        //$m = $item->precio * $item->descuento;
                        $t += ($item->precio) * $item -> cantidad * 0.16;
                    }
                }
            //Retornamos la vista y vaciamos el pedido actual
          // $vaciar = \Session::forget('cart');
           //$vaciarextra = \Session::forget('extras');

            return View::make('users/detalle',
                      compact(
                        'producto',
                        'total',
                        't',
                        'direc',
                        'cli',
                        'iddom',
                        'pedido',
                        'vaciar',
                        'vaciarextra',
                        'extra'
                        ));
            } else {
                if($idp == $idcliente){
                    $pedido = DB::table('pedido')
                     ->select('pedido.created_at','num_pedido')
                     ->where('pedido.id', $iddom)
                     ->take(1)
                     ->get();

                    $direc = DB::table('direccion_cliente')
                        ->join('pedido', 'direccion_cliente.id', '=', 'pedido.direccion_cliente_id')
                        ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
                        ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
                        ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
                        ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
                        ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
                        ->where("direccion_cliente.id", $iddi)
                        ->take(1)
                        ->get();

                    $producto = DB::table('producto')
                              ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                                ->where('pedido_detalle.pedido_id', $iddom)
                                ->select('clave', 'nombre', 'color', 'pedido_detalle.precio','iva0', 'cantidad', 'num_pedimento')
                                ->get();
                    $extra = DB::table('extra_pedido')
                        ->where('pedido_id', $iddom)
                        ->get();

                    //if(count(\Session::get('cart')) <= 0) return Redirect::to('users');
                    //subtotal
                    $total = 0;
                    foreach($producto as $item){
                       // $m = $item->precio * $item->descuento;
                        $total += ($item->precio) * $item ->cantidad;
                    }
                    //iva
                     $t = 0;
                    foreach($producto as $item){
                    if($item->iva0 == 0){
                    } else {
                        //$m = $item->precio * $item->descuento;
                        $t += ($item->precio) * $item -> cantidad * 0.16;
                    }
                }
                    //Retornamos la vista y vaciamos el pedido actual
                   //$vaciar = \Session::forget('cart');
                   //$vaciarextra = \Session::forget('extras');

                    return View::make('users/detalle',
                              compact(
                                'producto',
                                'total',
                                't',
                                'direc',
                                'iddom',
                                'pedido',
                                'vaciar',
                                'extra',
                                'vaciarextra'
                                ));
                    } else {
                        echo "Error, la página solicitada no existe.";
                    }
            }
        } else {
            return Redirect::to('login');
        }
    }


    public function imprimirpedido($id){
      if (Auth::check()) {
            $iduser = Auth::user()->id;
            $rol = Auth::user()->rol_id;
            $iddi = DB::table('pedido')
                 ->join('direccion_cliente', 'pedido.direccion_cliente_id', '=', 'direccion_cliente.id')
                 ->select('direccion_cliente.id')
                ->where('pedido.id', $id)
                ->pluck('direccion_cliente.id');
            $idcliente = DB::table('cliente')
                    ->where("usuario_id", $iduser)
                    ->pluck('id');
            $idp = DB::table('pedido')
                        ->where('direccion_cliente_id', $iddi)
                        ->pluck('cliente_id');
            $d = DB::table('pedido')
                    ->join('pedido_detalle','pedido.id', '=','pedido_detalle.pedido_id')
                    ->where('pedido.id', $id)
                    ->pluck('pedido_detalle.id');
            $idd = DB::table('pedido')
                    ->join('pedido_detalle','pedido.id', '=','pedido_detalle.pedido_id')
                    ->where('pedido.id', $id)
                    ->pluck('pedido_detalle.producto_id');
            $iddirec = DB::table('pedido')
                    ->join('direccion_cliente','pedido.direccion_cliente_id', '=','direccion_cliente.id')
                    ->where('pedido.id', $id)
                    ->pluck('pedido.direccion_cliente_id');
             $nivel = DB::table('cliente')
                ->join('nivel_descuento', 'cliente.nivel_descuento_id', '=', 'nivel_descuento.id')
                ->select('descripcion')
                ->where('cliente.usuario_id', $iduser)
                ->pluck('descripcion');
            if($iddi == NULL){
                $pedido = DB::table('pedido')
                        ->where('pedido.id', $id)
                        ->get();
                 $cli = DB::table('cliente')
                    ->join('pedido', 'cliente.id', '=', 'pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                 $domi = DB::table('direccion_cliente')
                    ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
                    ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
                    ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
                    ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
                    ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
                    ->where("direccion_cliente.id", $iddirec)
                    ->get();
                 $ped = DB::table('cliente')
                    ->join('pedido','cliente.id', '=','pedido.cliente_id')
                    ->where('pedido.id', $id)
                    ->get();
                  $pro = DB::table('producto')
                              ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                                ->where('pedido_detalle.pedido_id', $id)
                                ->select('clave', 'nombre', 'color', 'pedido_detalle.precio','iva0', 'cantidad', 'num_pedimento')
                                ->get();
                $extra = DB::table('extra_pedido')
                        ->where('pedido_id', $id)
                        ->get();

                $dpro = DB::table('pedido_detalle')
                            ->join('producto','pedido_detalle.producto_id', '=','producto.id')
                            ->where('pedido_detalle.id', $id)
                            ->get();
                   //Sacamos el iva
                    $total = 0;
                    foreach($pro as $item){
                    if($item->iva0 == 0){
                    } else {
                        //$m = $item->precio * $item->descuento;
                        $total += ($item->precio) * $item ->cantidad * 0.16;
                    }
                }
                   //Sacamos el subtotal
                     $t = 0;
                        foreach($pro as $item){
                           // $m = $item->precio * $item->descuento;
                            $t += ($item->precio) * $item ->cantidad;
                        }
                $pdf = View::make('users/report',
                        compact(
                            'dpro',
                            'pro',
                            'domi',
                            'ped',
                            'pedido',
                            'total',
                            't',
                            'cli',
                            'extra'
                            ));
                    return PDF::load($pdf, 'A4', 'portrait')->show();
                    //return PDF::load($pdf, 'A4', 'portrait')->download('my_pdf');

                     // return PDF::load($pdf, 'A4', 'portrait')->show();
            } else {
                $pedido = DB::table('pedido')
                        ->where('pedido.id', $id)
                        ->get();

                if($idp == $idcliente || $rol != 1){
                    $domi = DB::table('direccion_cliente')
                        ->join('cliente', 'direccion_cliente.cliente_id', '=', 'cliente.id')
                        ->join('pais', 'direccion_cliente.pais_id', '=', 'pais.id')
                        ->join('estado', 'direccion_cliente.estado_id', '=', 'estado.id')
                        ->join('municipio', 'direccion_cliente.municipio_id', '=', 'municipio.id')
                        ->join('telefono_cliente', 'direccion_cliente.telefono_cliente_id', '=', 'telefono_cliente.id')
                        ->where("direccion_cliente.id", $iddirec)
                        ->get();
                     $ped = DB::table('cliente')
                        ->join('pedido','cliente.id', '=','pedido.cliente_id')
                        ->where('pedido.id', $id)
                        ->get();
                        $pro = DB::table('producto')
                                  ->join('pedido_detalle','producto.id', '=','pedido_detalle.producto_id')
                                    ->where('pedido_detalle.pedido_id', $id)
                                    ->select('clave', 'nombre', 'color', 'pedido_detalle.precio','iva0', 'cantidad', 'num_pedimento')
                                    ->get();
                        $extra = DB::table('extra_pedido')
                        ->where('pedido_id', $id)
                        ->get();
                    $dpro = DB::table('pedido_detalle')
                                ->join('producto','pedido_detalle.producto_id', '=','producto.id')
                                ->where('pedido_detalle.id', $id)
                                ->get();
                    //Sacamos el iva
                     $total = 0;
                    foreach($pro as $item){
                    if($item->iva0 == 0){
                    } else {
                        //$m = $item->precio * $item->descuento;
                        $total += ($item->precio) * $item -> cantidad * 0.16;
                    }
                }
                    //Sacamos el subtotal
                     $t = 0;
                        foreach($pro as $item){
                           // $m = $item->precio * $item->descuento;
                            $t += ($item->precio) * $item ->cantidad;
                        }
                    $pdf = View::make('users/report',
                            compact(
                                'dpro',
                                'pro',
                                'domi',
                                'ped',
                                'pedido',
                                'total',
                                't',
                                'extra'
                                ));

                        return PDF::load($pdf, 'A4', 'portrait')->show();
                } else {
                    echo "Error, la página solicitada no existe.";
                }
            }
         } else {
            return Redirect::to('login');
        }
    }
}
