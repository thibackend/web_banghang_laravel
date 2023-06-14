<?php

namespace App\Providers;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use App\Models\ProductType;
use App\Models\Cart;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        view()->composer('header',function($view){
            $loai_sp = ProductType::all();
            $view->with('loai_sp',$loai_sp);
        });

        view()->composer('header',function ($view) {
            if(Session('cart')){
                $oldCart = Session::get('cart');
                $cart = new Cart($oldCart);
                $view->with(['cart' => Session::get('cart'),
                                        'product_cart' => $cart->items,
                                        'totalPrice' => $cart->totalPrice,
                                        'totalQty' => $cart->totalQty
                
                                        ]);
                                        }
        });
        //--------------------------------WISHLIST---------------------------------//
        // view()->composer('header', function ($view){
        //     if (Session('user')){
        //         $user = Session::get('user');
        //         $wishlists = Wishlist::where('id_user', $user->id)->get();
        //         $sumWishlist = 0;
        //         $totalWishlist = 0;
        //     }
        // });
    }
}
