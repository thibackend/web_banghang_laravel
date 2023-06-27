<?php

namespace App\Http\Controllers;
use App\Models\Slide;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\BillDetail;
use App\Models\Customer;
use App\Models\Cart;
use App\Models\Bill;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;



class PageController extends Controller
{
    public function getIndex(){
        $slide = Slide::all();
        $new_product = Product::where('new',1)->paginate(8);	
        $sanpham_khuyenmai = Product::where('promotion_price','<>',0)->paginate(8);	
        return view('page.trangchu',compact('slide','new_product','sanpham_khuyenmai'));
        
    }
    public function getLoaiSP($type){
        $sp_theoloai = Product::where('id_type',$type)->get();
        $sp_khac = Product::where('id_type','<>',$type)->paginate(3);
        $loai = ProductType::all();
        $l_sanpham = ProductType::where('id',$type)->get();
        return view('page.loai_sanpham',compact('sp_theoloai','sp_khac','loai','l_sanpham'));
    }

    public function getDetail(Request $req){
        $sanpham = Product::where('id', $req->id)->first();
        // $splienquan = Product::where('id','<>',$sanpham->id,'and','id_type','=',$sanpham->id_type,)->paginate(3);
        return view('page.chitiet_sanpham',compact('sanpham'));
    }

    public function getContact(){
        return view('page.lienhe');
    }

    public function getAbout(){
        return view('page.gioithieu');
    }

    //  Admin
    public function getIndexAdmin()
    {
        $products = Product::all();
        return view('pageadmin.admin')->with(['product' => $products, 'sumSold' => count(BillDetail::all())]);
    }

    public function getAdminAdd()
    {
        return view('pageadmin.formAdd');
    }
    public function postAdminAdd(Request $request)
    {
        $product = new product();
        if ($request->hasFile('inputImage')) {
            $file = $request->file('inputImage');
            $fileName = $file->getClientOriginalName('inputImage');
            $file->move('source/image/product', $fileName);
        }
        $file_name = null;
        if ($request->file('inputImage') != null) {
            $file_name = $request->file('inputImage')->getClientOriginalName();
        }

        $product->name = $request->inputName;
        $product->image = $file_name;
        $product->description = $request->inputDescription;
        $product->unit_price = $request->inputPrice;
        $product->promotion_price = $request->inputPromotionPrice;
        $product->unit = $request->inputUnit;
        $product->new = $request->inputNew;
        $product->id_type = $request->inputType;
        $product->save();
        return $this->getIndexAdmin();
    }

    public function getAdminEdit($id)
    {
        $product = Product::find($id);
        return view('pageadmin.formEdit')->with('product', $product);
    }

    public function postAdminEdit(Request $request)
    {
        $id = $request->editId;
        $product =  Product::find($id);
        if ($request->hasFile('editImage')) {
            $file = $request->file('editImage');
            $fileName = $file->getClientOriginalName('editImage');
            $file->move('source/image/product', $fileName);
        }
        if ($request->file('editImage') != null) {
            $product->image =$fileName;
        }

        $product->name = $request->editName;
        $product->description = $request->editDescription;
        $product->unit_price = $request->editPrice;
        $product->promotion_price = $request->editPromotionPrice;
        $product->unit = $request->editUnit;
        $product->new = $request->editNew;
        $product->id_type = $request->editType;
        $product->save();
        return $this->getIndexAdmin();
    }
    public function postAdminDelete($id)
    {
      $product = Product::find($id);
      $product->delete();
      return $this->getIndexAdmin();  
    }

    // Cart			
    // Không cần đăng nhập vẫn mua hàng được 
    					
    // public function getAddToCart(Request $req, $id){					
    //     $product = Product::find($id);					
    //     $oldCart = Session('cart')?Session::get('cart'):null;					
    //     $cart = new Cart($oldCart);					
    //     $cart->add($product,$id);					
    //     $req->session()->put('cart', $cart);					
    //     return redirect()->back();					
    // }	
    
    // Bắt buộc đăng nhập mới mua hàng
    public function getAddToCart(Request $req, $id)
    {
        if (Session::has('user')) {
            if (Product::find($id)) {
                $product = Product::find($id);
                $oldCart = Session('cart') ? Session::get('cart') : null;
                $cart = new Cart($oldCart);
                $cart->add($product, $id);
                $req->session()->put('cart', $cart);
                return redirect()->back();
            } else {
                return '<script>alert("Không tìm thấy sản phẩm này.");window.location.assign("/");</script>';
            }
        } else {
            return '<script>alert("Vui lòng đăng nhập để sử dụng chức năng này.");window.location.assign("/login");</script>';
        }
    }
      
    public function getDelItemCart($id){
        $oldCart = Session::has('cart')?Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->removeItem($id);
        if(count($cart->items)>0){
        Session::put('cart',$cart);

        }
        else{
            Session::forget('cart');
        }
        return redirect()->back();
    }		
    //----------------------------CHECKOUT-----------------------//
    
    public function getCheckout()
    {
      if(Session::has('cart')){
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        return view('page.checkout')->with(['cart' => Session::get('cart'),
                                                        'product_cart'=>$cart->items,
                                                        'totalPrice'=> $cart->totalPrice,
                                                        'totalQty'=>$cart->totalQty]);
      }  else{
        return redirect('page');
      }
    }

	public function postCheckout(Request $req){
		$cart = Session::get('cart');
		$customer = new Customer;
		$customer->name = $req->full_name;
		$customer->gender = $req->gender;
		$customer->email = $req->email;
		$customer->address = $req->address;
		$customer->phone_number = $req->phone;

		if(isset($req->notes)){
			$customer->note = $req->notes;
		} else{
			$customer->note = "Không có ghi chú gì";
		}

		$customer->save();

		$bill = new Bill;
		$bill->id_customer = $customer->id;
		$bill->date_order = date('Y-m-d');
		$bill->total = $cart->totalPrice;
		$bill->payment = $req->payment_method;
		if(isset($req->notes)){
			$bill->note = $req->notes;
		}else{
			$bill->note = "Không có ghi chú gì";
		}
		$bill->save();

		foreach($cart->items as $key =>$value){
			$bill_detail = new BillDetail;
			$bill_detail->id_bill = $bill->id;
			$bill_detail->id_product = $key;
			$bill_detail->quantity = $value['qty'];
			$bill_detail->unit_price = $value['price'] / $value['qty'];
		}

		Session::forget('cart');
		$wishlists = Wishlist::where('id_user', Session::get('user')->id)->get();
		if(isset($wishlists)){
			foreach($wishlists as $element){
				$element->delete();
			}
		}

	}

    //-----------------------------------Cổng Thanh Toán VNPAY--------------------------------------//

    public function vnp_payment(){
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        /*
        * To change this license header, choose License Headers in Project Properties.
        * To change this template file, choose Tools | Templates
        * and open the template in the editor.
        */
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_HashSecret = "ZPPCLYQFPCXMQOXEEYGGDONQRVRHNJRG"; //Secret key
        $vnp_TmnCode = "QVF8FLJ1"; //Website ID in VNPAY System
        $vnp_Returnurl = "http://localhost/vnpay_php/vnpay_return.php";
        // $vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
        //Config input format
        //Expire
        $startTime = date("YmdHis");
        $expire = date('YmdHis',strtotime('+15 minutes',strtotime($startTime)));

        $vnp_TxnRef = '1256'; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        $vnp_OrderInfo = 'Thanh toán đơn hàng test';
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = 10000 * 100;
        $vnp_Locale = 'VN';
        $vnp_BankCode = 'NCB';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        //Add Params of 2.0.1 Version
        // $vnp_ExpireDate = $_POST['txtexpire'];
        //Billing
        // $vnp_Bill_Mobile = $_POST['txt_billing_mobile'];
        // $vnp_Bill_Email = $_POST['txt_billing_email'];
        // $fullName = trim($_POST['txt_billing_fullname']);
        // if (isset($fullName) && trim($fullName) != '') {
        //     $name = explode(' ', $fullName);
        //     $vnp_Bill_FirstName = array_shift($name);
        //     $vnp_Bill_LastName = array_pop($name);
        // }
        // $vnp_Bill_Address=$_POST['txt_inv_addr1'];
        // $vnp_Bill_City=$_POST['txt_bill_city'];
        // $vnp_Bill_Country=$_POST['txt_bill_country'];
        // $vnp_Bill_State=$_POST['txt_bill_state'];
        // // Invoice
        // $vnp_Inv_Phone=$_POST['txt_inv_mobile'];
        // $vnp_Inv_Email=$_POST['txt_inv_email'];
        // $vnp_Inv_Customer=$_POST['txt_inv_customer'];
        // $vnp_Inv_Address=$_POST['txt_inv_addr1'];
        // $vnp_Inv_Company=$_POST['txt_inv_company'];
        // $vnp_Inv_Taxcode=$_POST['txt_inv_taxcode'];
        // $vnp_Inv_Type=$_POST['cbo_inv_type'];
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
            // "vnp_ExpireDate"=>$vnp_ExpireDate
            // "vnp_Bill_Mobile"=>$vnp_Bill_Mobile,
            // "vnp_Bill_Email"=>$vnp_Bill_Email,
            // "vnp_Bill_FirstName"=>$vnp_Bill_FirstName,
            // "vnp_Bill_LastName"=>$vnp_Bill_LastName,
            // "vnp_Bill_Address"=>$vnp_Bill_Address,
            // "vnp_Bill_City"=>$vnp_Bill_City,
            // "vnp_Bill_Country"=>$vnp_Bill_Country,
            // "vnp_Inv_Phone"=>$vnp_Inv_Phone,
            // "vnp_Inv_Email"=>$vnp_Inv_Email,
            // "vnp_Inv_Customer"=>$vnp_Inv_Customer,
            // "vnp_Inv_Address"=>$vnp_Inv_Address,
            // "vnp_Inv_Company"=>$vnp_Inv_Company,
            // "vnp_Inv_Taxcode"=>$vnp_Inv_Taxcode,
            // "vnp_Inv_Type"=>$vnp_Inv_Type
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
            $inputData['vnp_Bill_State'] = $vnp_Bill_State;
        }

        //var_dump($inputData);
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);//  
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        $returnData = array('code' => '00', 'message' => 'success', 'data' => $vnp_Url);
            if (isset($_POST['redirect'])) {
                header('Location: ' . $vnp_Url);
                die();
            } else {
                echo json_encode($returnData);
            }

    }
    
}