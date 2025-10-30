<?php
// ControllersBundle.php
// Single file containing multiple controllers for Blade+Bootstrap Laravel eCommerce
// Drop this file into app/Http/Controllers/ControllersBundle.php and then split into separate files if desired.
// Remember to run: composer require stripe/stripe-php
// Add STRIPE_KEY and STRIPE_SECRET to .env
// Adjust policies/middleware according to your app (e.g., is_admin gate).

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Wishlist;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\Review;
use App\Models\Payment;
use App\Mail\OrderPlaced;
use Stripe\Stripe;
use Stripe\PaymentIntent;

/*
 * Note:
 * - These controllers return Blade views. Ensure views exist under resources/views/.
 * - This file groups controllers for convenience. You can split classes into separate files later.
 * - Make sure your models have relations used here (e.g., Product->images(), Product->variants(), User->orders()).
 */

/**
 * AuthController - manual register/login/logout/profile (Blade)
 */
class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $req)
    {
        $data = $req->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        Auth::login($user);
        return redirect()->route('home')->with('success', 'Registration successful');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $req)
    {
        $creds = $req->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($creds, $req->boolean('remember'))) {
            $req->session()->regenerate();
            return redirect()->intended(route('home'));
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
    }

    public function logout(Request $req)
    {
        Auth::logout();
        $req->session()->invalidate();
        $req->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('auth.profile', compact('user'));
    }

    public function updateProfile(Request $req)
    {
        $user = Auth::user();
        $data = $req->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => 'nullable|confirmed|min:6'
        ]);
        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) $user->password = Hash::make($data['password']);
        $user->save();
        return back()->with('success', 'Profile updated');
    }
}

/**
 * ProductController - public listing, detail, search/filter (Blade)
 */
class ProductController extends Controller
{
    public function index(Request $req)
    {
        $query = Product::with('images','variants','categories')->where('is_active', true);

        if ($req->filled('q')) {
            $q = $req->input('q');
            $query->where(function($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%")
                   ->orWhere('slug', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($req->filled('category')) {
            $query->whereHas('categories', function($q) use ($req) {
                $q->where('slug', $req->category);
            });
        }

        if ($req->filled('min_price')) $query->where('price', '>=', $req->min_price);
        if ($req->filled('max_price')) $query->where('price', '<=', $req->max_price);

        $products = $query->paginate(12)->withQueryString();
        return view('products.index', compact('products'));
    }

    public function show($slug)
    {
        $product = Product::with('images','variants','reviews.user')->where('slug', $slug)->firstOrFail();
        return view('products.show', compact('product'));
    }
}

/**
 * CategoryController - public view + admin CRUD (simple)
 */
class CategoryController extends Controller
{
    public function index(Request $req, $slug = null)
    {
        if ($slug) {
            $category = Category::where('slug', $slug)->firstOrFail();
            $products = $category->products()->paginate(12);
            return view('categories.show', compact('category','products'));
        }

        $categories = Category::with('children')->get();
        return view('categories.index', compact('categories'));
    }

    // Admin methods (assumes 'can:access-admin' middleware is applied in routes)
    public function store(Request $req)
    {
        $data = $req->validate(['name'=>'required','slug'=>'required|unique:categories,slug','parent_id'=>'nullable|exists:categories,id']);
        Category::create($data);
        return back()->with('success','Category created');
    }

    public function update(Request $req, Category $category)
    {
        $data = $req->validate(['name'=>'required','slug'=>"required|unique:categories,slug,{$category->id}",'parent_id'=>'nullable|exists:categories,id']);
        $category->update($data);
        return back()->with('success','Category updated');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('success','Deleted');
    }
}

/**
 * CartController - session-based cart example
 */
class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        return view('cart.index', compact('cart'));
    }

    public function add(Request $req)
    {
        $data = $req->validate(['product_id'=>'required|exists:products,id','quantity'=>'nullable|integer|min:1','variant_id'=>'nullable|integer']);
        $product = Product::findOrFail($data['product_id']);
        $qty = $data['quantity'] ?? 1;
        $key = $product->id . (isset($data['variant_id']) ? ':'.$data['variant_id'] : '');

        $cart = session()->get('cart', []);
        if (isset($cart[$key])) $cart[$key]['quantity'] += $qty;
        else $cart[$key] = [
            'product_id'=>$product->id,
            'title'=>$product->title,
            'price'=>$product->price,
            'quantity'=>$qty,
            'variant_id'=>$data['variant_id'] ?? null,
            'image'=>$product->featured_image ?? ($product->images->first()->path ?? null)
        ];
        session()->put('cart', $cart);
        return back()->with('success','Added to cart');
    }

    public function update(Request $req)
    {
        $data = $req->validate(['key'=>'required','quantity'=>'required|integer|min:1']);
        $cart = session()->get('cart', []);
        if (isset($cart[$data['key']])) {
            $cart[$data['key']]['quantity'] = $data['quantity'];
            session()->put('cart', $cart);
            return back()->with('success','Cart updated');
        }
        return back()->withErrors('Item not found');
    }

    public function remove(Request $req)
    {
        $key = $req->input('key');
        $cart = session()->get('cart', []);
        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('cart', $cart);
        }
        return back()->with('success','Removed');
    }

    public function clear()
    {
        session()->forget('cart');
        return back()->with('success','Cart cleared');
    }
}

/**
 * WishlistController - DB-based wishlist
 */
class WishlistController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function index()
    {
        $items = Auth::user()->wishlist()->with('product')->paginate(20);
        return view('wishlist.index', compact('items'));
    }

    public function add(Request $req)
    {
        $req->validate(['product_id'=>'required|exists:products,id']);
        Wishlist::firstOrCreate(['user_id'=>Auth::id(),'product_id'=>$req->product_id]);
        return back()->with('success','Added to wishlist');
    }

    public function remove(Request $req)
    {
        Wishlist::where('user_id',Auth::id())->where('product_id',$req->product_id)->delete();
        return back()->with('success','Removed');
    }
}

/**
 * CheckoutController + Stripe handling
 */
class CheckoutController extends Controller
{
    public function show()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) return redirect()->route('cart.index')->withErrors('Cart empty');
        $coupon = session()->get('coupon');
        return view('checkout.index', compact('cart','coupon'));
    }

    // Apply coupon (simple example)
    public function applyCoupon(Request $req)
    {
        $req->validate(['code'=>'required|string']);
        $coupon = Coupon::where('code', $req->code)->where('is_active', true)
            ->where(function($q){ $q->whereNull('expires_at')->orWhere('expires_at','>',now()); })
            ->first();
        if (!$coupon) return back()->withErrors('Invalid/expired coupon');
        session()->put('coupon', $coupon->only(['id','code','discount_type','value']));
        return back()->with('success','Coupon applied');
    }

    // Create order and (optionally) create Stripe payment intent
    public function process(Request $req)
    {
        $req->validate(['name'=>'required','email'=>'required|email','address'=>'required','payment_method'=>'required|string']);
        $cart = session()->get('cart', []);
        if (empty($cart)) return back()->withErrors('Cart empty');

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($cart as $row) {
                $total += ($row['price'] * $row['quantity']);
            }
            // apply coupon if any
            if ($coupon = session()->get('coupon')) {
                if ($coupon['discount_type'] === 'percent') $total = $total - ($total * ($coupon['value']/100));
                else $total = max(0, $total - $coupon['value']);
            }

            // If payment method is stripe, create a Stripe PaymentIntent and return client secret for frontend
            if ($req->payment_method === 'stripe') {
                Stripe::setApiKey(config('services.stripe.secret', env('STRIPE_SECRET')));
                $intent = PaymentIntent::create([
                    'amount' => round($total * 100), // in cents/paise
                    'currency' => 'usd', // change as per requirement
                    'metadata' => ['integration_check'=>'accept_a_payment']
                ]);
                // return client secret so frontend Stripe JS can confirm payment
                return back()->with('stripe_client_secret', $intent->client_secret);
            }

            // For non-instant payments (e.g., cod), create order directly
            $order = Order::create([
                'user_id' => Auth::id() ?? null,
                'name' => $req->name,
                'email' => $req->email,
                'address' => $req->address,
                'status' => 'pending',
                'payment_method' => $req->payment_method,
                'total' => $total
            ]);

            foreach ($cart as $row) {
                OrderItem::create([
                    'order_id'=>$order->id,
                    'product_id'=>$row['product_id'],
                    'quantity'=>$row['quantity'],
                    'price'=>$row['price'],
                    'subtotal'=>$row['price']*$row['quantity']
                ]);
                // decrement stock
                $p = Product::find($row['product_id']);
                if ($p) $p->decrement('stock', $row['quantity']);
            }

            // send emails
            try {
                Mail::to($order->email)->send(new \App\Mail\OrderPlaced($order));
                // notify admin - admin email from config
                $adminEmail = config('mail.admin_email', env('ADMIN_EMAIL'));
                if ($adminEmail) Mail::to($adminEmail)->send(new \App\Mail\OrderPlaced($order));
            } catch (\Exception $e) {
                // don't fail order for mail issues
            }

            DB::commit();
            session()->forget(['cart','coupon']);
            return redirect()->route('orders.show', $order->id)->with('success','Order placed');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error creating order: '.$e->getMessage());
        }
    }

    // Endpoint for Stripe webhook or success callback after client confirms payment on frontend
    public function stripeSuccess(Request $req)
    {
        // This endpoint should be called after Stripe confirms payment on frontend.
        // The frontend should send payment_intent_id and cart/order payload, or use webhook to create/confirm order server-side.
        $req->validate(['payment_intent'=>'required','name'=>'required','email'=>'required','address'=>'required']);
        $paymentIntentId = $req->payment_intent;
        // retrieve details if needed via Stripe SDK (omitted for brevity)
        // create order similar to above and mark payment status as paid
        $cart = session()->get('cart', []);
        if (empty($cart)) return back()->withErrors('Cart empty');

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($cart as $row) $total += ($row['price']*$row['quantity']);
            $order = Order::create([
                'user_id' => Auth::id() ?? null,
                'name' => $req->name,
                'email' => $req->email,
                'address' => $req->address,
                'status' => 'processing',
                'payment_method' => 'stripe',
                'payment_intent_id' => $paymentIntentId,
                'total' => $total
            ]);
            foreach ($cart as $row) {
                OrderItem::create([
                    'order_id'=>$order->id,
                    'product_id'=>$row['product_id'],
                    'quantity'=>$row['quantity'],
                    'price'=>$row['price'],
                    'subtotal'=>$row['price']*$row['quantity']
                ]);
                $p = Product::find($row['product_id']);
                if ($p) $p->decrement('stock', $row['quantity']);
            }

            // send emails
            try {
                Mail::to($order->email)->send(new \App\Mail\OrderPlaced($order));
                $adminEmail = config('mail.admin_email', env('ADMIN_EMAIL'));
                if ($adminEmail) Mail::to($adminEmail)->send(new \App\Mail\OrderPlaced($order));
            } catch (\Exception $e){}

            DB::commit();
            session()->forget(['cart','coupon']);
            return redirect()->route('orders.show', $order->id)->with('success','Payment successful and order created');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error finishing order: '.$e->getMessage());
        }
    }
}

/**
 * OrderController - user orders
 */
class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::user() ? Auth::user()->orders()->latest()->paginate(20) : collect();
        return view('orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with('items.product')->findOrFail($id);
        // authorize: only owner or admin
        if (Auth::id() !== $order->user_id && !Auth::user()?->is_admin) abort(403);
        return view('orders.show', compact('order'));
    }
}

/**
 * ReviewController - add review with images (frontend WebRTC video upload handled separately)
 */
class ReviewController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function store(Request $req, $productId)
    {
        $product = Product::findOrFail($productId);
        $data = $req->validate(['rating'=>'required|integer|min:1|max:5','comment'=>'nullable|string','images.*'=>'nullable|image|max:5120']);
        $review = $product->reviews()->create([
            'user_id' => Auth::id(),
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null
        ]);
        if ($req->hasFile('images')) {
            foreach ($req->file('images') as $f) {
                $path = $f->store('reviews','public');
                $review->images()->create(['path'=>$path]);
            }
        }
        return back()->with('success','Review submitted');
    }
}

/**
 * CouponController - admin manage + frontend apply handled earlier in CheckoutController
 */
class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::paginate(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function store(Request $req)
    {
        $data = $req->validate(['code'=>'required|unique:coupons,code','discount_type'=>'required|in:percent,fixed','value'=>'required|numeric','expires_at'=>'nullable|date','usage_limit'=>'nullable|integer']);
        Coupon::create($data);
        return back()->with('success','Coupon created');
    }

    public function update(Request $req, Coupon $coupon)
    {
        $data = $req->validate(['code'=>"required|unique:coupons,code,{$coupon->id}",'discount_type'=>'required|in:percent,fixed','value'=>'required|numeric','expires_at'=>'nullable|date','usage_limit'=>'nullable|integer']);
        $coupon->update($data);
        return back()->with('success','Updated');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return back()->with('success','Deleted');
    }
}

/**
 * Admin controllers (basic)
 */
class AdminDashboardController extends Controller
{
    public function __construct() { $this->middleware(['auth','can:access-admin']); }

    public function index()
    {
        $totalSales = Order::where('status','delivered')->sum('total');
        $totalUsers = User::count();
        $topProducts = Product::withCount('orderItems')->orderBy('order_items_count','desc')->take(6)->get();
        return view('admin.dashboard', compact('totalSales','totalUsers','topProducts'));
    }
}

class AdminProductController extends Controller
{
    public function __construct() { $this->middleware(['auth','can:access-admin']); }

    public function index() { $products = Product::paginate(20); return view('admin.products.index', compact('products')); }

    public function create() { $categories = Category::all(); return view('admin.products.create', compact('categories')); }

    public function store(Request $req)
    {
        $data = $req->validate(['title'=>'required','slug'=>'required|unique:products,slug','description'=>'nullable','price'=>'required|numeric','discounted_price'=>'nullable|numeric','sku'=>'nullable|string','stock'=>'required|integer','is_active'=>'boolean','is_featured'=>'boolean','category_ids'=>'nullable|array','images.*'=>'nullable|image']);
        $product = Product::create($req->only(['title','slug','description','price','discounted_price','sku','stock','is_active','is_featured']));
        if ($req->filled('category_ids')) $product->categories()->sync($req->category_ids);
        if ($req->hasFile('images')) {
            foreach ($req->file('images') as $f) {
                $path = $f->store('products','public');
                $product->images()->create(['path'=>$path]);
            }
        }
        return redirect()->route('admin.products.index')->with('success','Created');
    }

    public function edit(Product $product) { $categories = Category::all(); return view('admin.products.edit', compact('product','categories')); }

    public function update(Request $req, Product $product)
    {
        $data = $req->validate(['title'=>'required','slug'=>"required|unique:products,slug,{$product->id}",'description'=>'nullable','price'=>'required|numeric','discounted_price'=>'nullable|numeric','sku'=>'nullable|string','stock'=>'required|integer','is_active'=>'boolean','is_featured'=>'boolean','category_ids'=>'nullable|array','images.*'=>'nullable|image']);
        $product->update($req->only(['title','slug','description','price','discounted_price','sku','stock','is_active','is_featured']));
        if ($req->filled('category_ids')) $product->categories()->sync($req->category_ids);
        if ($req->hasFile('images')) {
            foreach ($req->file('images') as $f) {
                $path = $f->store('products','public');
                $product->images()->create(['path'=>$path]);
            }
        }
        return back()->with('success','Updated');
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $img) Storage::disk('public')->delete($img->path);
        $product->delete();
        return back()->with('success','Deleted');
    }
}

class AdminOrderController extends Controller
{
    public function __construct() { $this->middleware(['auth','can:access-admin']); }

    public function index(Request $req)
    {
        $q = Order::with('user');
        if ($req->filled('status')) $q->where('status', $req->status);
        $orders = $q->latest()->paginate(30);
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order) { $order->load('items.product','user'); return view('admin.orders.show', compact('order')); }

    public function updateStatus(Request $req, Order $order)
    {
        $req->validate(['status'=>'required|string']);
        $order->status = $req->status;
        $order->save();
        return back()->with('success','Order status updated');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return back()->with('success','Deleted');
    }
}

class AdminUserController extends Controller
{
    public function __construct() { $this->middleware(['auth','can:access-admin']); }

    public function index() { $users = User::paginate(20); return view('admin.users.index', compact('users')); }

    public function show(User $user) { $orders = $user->orders()->latest()->paginate(15); return view('admin.users.show', compact('user','orders')); }

    public function toggleBlock(User $user) { $user->is_active = !$user->is_active; $user->save(); return back()->with('success','Status updated'); }

    public function updateRole(Request $req, User $user) { $req->validate(['role'=>'required|string']); $user->role = $req->role; $user->save(); return back()->with('success','Role updated'); }
}

class AdminCouponController extends Controller
{
    public function __construct() { $this->middleware(['auth','can:access-admin']); }

    public function index() { $coupons = Coupon::paginate(20); return view('admin.coupons.index', compact('coupons')); }
    public function create() { return view('admin.coupons.create'); }
    public function store(Request $req) { $data = $req->validate(['code'=>'required|unique:coupons,code','discount_type'=>'required|in:percent,fixed','value'=>'required|numeric','expires_at'=>'nullable|date','usage_limit'=>'nullable|integer']); Coupon::create($data); return back()->with('success','Created'); }
    public function edit(Coupon $coupon) { return view('admin.coupons.edit', compact('coupon')); }
    public function update(Request $req, Coupon $coupon) { $data = $req->validate(['code'=>"required|unique:coupons,code,{$coupon->id}",'discount_type'=>'required|in:percent,fixed','value'=>'required|numeric','expires_at'=>'nullable|date','usage_limit'=>'nullable|integer']); $coupon->update($data); return back()->with('success','Updated'); }
    public function destroy(Coupon $coupon) { $coupon->delete(); return back()->with('success','Deleted'); }
}

return;
