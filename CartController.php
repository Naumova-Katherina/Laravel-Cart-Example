<?php

namespace App\Http\Controllers\Cart;

use App;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Services\CurrencyService;
use App\Http\Services\ImageService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /* Display Cart */
    public function index($lang, Request $request) {

        //set the language
        App::setLocale($lang);

        $total = 0;

        //get the currency Abb
        $currency_abb = (new CurrencyService())->getCurrencyAbb();

        // get the cart out of session
        $cart = session()->get('cart');
        if(!$cart) {
            $count = 0;
        } else {
            foreach($cart as $item) {
               foreach($item as $key => $value) {
                    if($key === 'id') {
                       $id = $value;
                    }
                    if($key === 'price') {
                        $total += ($value * $cart[$id]['quantity']);
                    }
                    if($key === 'image') {
                       $cart[$id][$key] = (new ImageService())->getSingleImagePath('products', $id, $value);
                   }
               }
            }
            session()->put('cart', $cart);

            // count the items in cart
            $count = $this->countItems($cart);
        }

        session()->put('count', $count);
        session()->put('total', $total);
        

        return view('website.cart.cart')
            ->with('lang', $lang)
            ->with('cart', $cart)
            ->with('currency_abb', $currency_abb)
            ->with('count', $count)
            ->with('total', $total);
    }

    public function addToCart($lang, $id, $count)
    {
        $item = Product::find($id);

        if(!$item) {
            abort(404);
        }

        $cart = session()->get('cart');

        // if cart is empty then this is the first product
        if(!$cart) {
            $cart = [
                $id => [
                    "id" => $item->id,
                    "slug" => $item->slug,
                    "image" => $item->image,
                    "currency" => $item->currency_id,
                    "product_name" => $item->product_name,
                    "quantity" => $count,
                    "price" => $item->price
                ]
            ];

            //get the currency Abb
            $currency_abb = (new CurrencyService())->getCurrencyAbb();

            session()->put('cart', $cart);
            session()->put('count', $count);
            session()->put('total', $item->price);

            return redirect()->back();
        }

        // if cart not empty then check if this product exist then increment quantity
        if(isset($cart[$id])) {

            $cart[$id]['quantity'] = $cart[$id]['quantity'] + $count;

            $count = $this->countItems($cart);
            $total = $this->countTotal($cart);

            session()->put('cart', $cart);
            session()->put('count', $count);
            session()->put('total', $total);

            return redirect()->back();
        }

        // if item not exist in cart then add to cart
        $cart[$id] = [
            "id" => $item->id,
            "slug" => $item->slug,
            "image" => $item->image,
            "currency" => $item->currency_id,
            "product_name" => $item->product_name,
            "quantity" => $count,
            "price" => $item->price
        ];

        $count = $this->countItems($cart);
        $total = $this->countTotal($cart);

        // put new data
        session()->put('cart', $cart);
        session()->put('count', $count);
        session()->put('total', $total);

        return redirect()->back();
    }

    public function clearAll($lang) {

        // remove all items out of cart
        $cart = session()->forget('cart');
        $cart = session()->forget('total');
        $cart = session()->forget('count');

        return redirect()->route('cart', ['lang' => $lang]);

    }

    public function deleteOne($lang, $id) {

        //get  the cart data
        $cart = session()->get('cart');

        if(isset($cart[$id])) {

            // delete the element
            unset($cart[$id]);

            // put new data
            session()->put('cart', $cart);
        }
        return redirect()->route('cart', ['lang' => $lang]);

    }

    public function changeAmount($lang, $id, $count) {
        //get the cart 
        $cart = session()->get('cart');

        if(isset($cart[$id])) {

            //add the amount to cart if that element exists
            $cart[$id]['quantity'] = $cart[$id]['quantity'] + $count;

            //check if item quantity is not 0
            if ($cart[$id]['quantity'] <= 0) {

                unset($cart[$id]);

                //check if cart is empty
                if (empty($cart)) {

                    unset($cart);
                    session()->forget('cart');
                }
            }

            //update session of cart if it still has elements
            if (!empty($cart)) {

                session()->put('cart', $cart);

                //calculate new total
                $total = $this->countTotal($cart);

                session()->put('total', $total);
            }
        }
    }

    public function minicart(Request $request) {

        $data = [];

        // get the cart data
        $cart = session()->get('cart');

        //get the currency data
        $data['currency_abb'] = (new CurrencyService())->getCurrencyAbb();

        if($cart) {
            $data['count'] = $this->countItems($cart);
            $data['total'] = $this->countTotal($cart);
        }

         // put new data
        session()->put('total', $data['total']);

        return $data;
    }

    /* Counting the amount of all items in cart */
    public function countItems($cart) {
        $count = 0;
        if ($cart) {
            foreach ($cart as $item) {
                foreach ($item as $key => $value) {
                    if ($key === 'quantity') {
                        $count += $value;
                    }
                }
            }
        }

        return $count;
    }

    /* Counting the total price of all items in cart */
    public function countTotal($cart) {
        $total = 0;
        if ($cart) {
            foreach ($cart as $item) {
                foreach ($item as $key => $value) {
                    if($key === 'id') {
                        $id = $value;
                    }
                    if($key === 'price') {
                        $total += ($value * $cart[$id]['quantity']);
                    }
                }
            }
        }

        return $total;
    }

}
