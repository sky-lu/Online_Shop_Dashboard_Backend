<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOrderFlash;



class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $allOrder = Order::whereNotNull('id');
        $allOrder = Order::with(['vat', 'flashes']);


        if($request->has('customer_id')){
            $allOrder = Order::where('customer_id', $request->customer_id);
        }
        if($request->has('subtotal')){
            $allOrder = Order::where('subtotal', '>', $request->subtotal);
        }
        if($request->has('vat_id')){
            $allOrder = Order::where('vat_id', $request->vat_id);
        }
        if($request->has('status')){
            $allOrder = Order::where('status', $request->status);
        }

        return $allOrder->paginate(3)->toJson();
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $form = $request->all();
        // the product_quantity will be transported like {product_id:product_number}
        $product_infos = $request->product_quantity;
        $infos = json_decode($product_infos, true);
        
        $subtotal = 0;
        foreach ($infos as $x => $x_value){
            $product = Product::where('id', $x)
                             ->first();
            $subtotal = $subtotal + $product->selling_price * $x_value;
        }

        $form['subtotal'] = $subtotal;
        
        $lastOrder = Order::create($form)->latest('id')->get();
        // store data to the product-order-flash table
        $orderId = $lastOrder[0]->id;
        foreach ($infos as $x => $x_value){
            $product = Product::where('id', $x)
                             ->first()->toJson();
            $flash = new ProductOrderFlash();
            $flash->product_id = $x;
            $flash->order_id = $orderId;
            $flash->product_info = $product;
            $flash->save();
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = Order::find($id);
        if ($order){
            $order->vat = $order->vat;
            $order->flashes = $order->flashes;
            return $order->toJson();
        }else{
            return "Invalid Id !";
        }
    }

    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Order::where('id', $id)
                ->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
       Order::destroy($id);
    }
}