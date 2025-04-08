<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = auth()->user()->purchases()->with('product')->get();
        
        // حساب الإجمالي
        $total = $purchases->sum('price');
        
        return view('purchases.index', compact('purchases', 'total'));
    }
    public function purchase($productId)
{
    $product = Product::findOrFail($productId);
    $user = Auth::user();

    // التحقق من الرصيد والمخزون
    if($user->credit < $product->price) {
        return back()->with('error', 'رصيدك غير كافي');
    }

    if($product->stock < 1) {
        return back()->with('error', 'المنتج غير متوفر في المخزون');
    }

    // تنفيذ عملية الشراء
    $user->credit -= $product->price;
    $user->save();

    $product->stock -= 1;
    $product->save();

    // تسجيل العملية في جدول المشتريات
    Purchase::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'price' => $product->price,
        'purchased_at' => now()
    ]);

    return redirect()->route('purchases.index')->with('success', 'تمت عملية الشراء بنجاح');
}
}