<!DOCTYPE html>
<html lang="en" data-theme="dilg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - DILG-RC System</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    
    <!-- Vite Assets (Tailwind CSS + DaisyUI) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#172033]">
    
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-6xl grid lg:grid-cols-2 gap-0 bg-white rounded-3xl shadow-2xl overflow-hidden border border-white/10">
            
            <!-- Left Panel - DILG Yellow Background -->
            <div class="bg-gradient-to-br from-[#F4C542] to-[#D4A017] p-7 sm:p-10 lg:p-12 flex flex-col justify-center items-center text-center lg:text-left relative overflow-hidden">
                <!-- Decorative circles -->
                <div class="absolute -top-20 -left-20 w-60 h-60 bg-white/10 rounded-full"></div>
                <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-white/10 rounded-full"></div>
                
                <div class="relative z-10 w-full max-w-md">
                    <!-- DILG Logo - Made Bigger -->
                    <div class="mb-8 flex justify-center lg:justify-start">
                        <img src="{{ asset('images/dilg-logo.png') }}" alt="DILG Logo" class="w-48 h-32 lg:w-56 lg:h-40 object-contain drop-shadow-xl">
                    </div>
                    
                    <!-- Welcome Text -->
                    <div class="text-white mb-8">
                        <h1 class="text-3xl lg:text-5xl font-bold mb-4 tracking-tight">Road Clearing Operations</h1>
                        <p class="text-lg opacity-90">Sign in to access the Road Clearing Violation Reporting System</p>
                    </div>
                    
                    <!-- System Info Card -->
                    <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-6 border border-white/30">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-map-marker-alt text-white text-xl"></i>
                            <h3 class="text-white font-semibold text-lg">Santa Cruz, Laguna</h3>
                        </div>
                        <p class="text-white/90 text-sm">
                            Department of the Interior and Local Government<br>
                            Road Clearing Operations Center
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel - Login Form -->
            <div class="p-7 sm:p-10 lg:p-12 flex flex-col justify-center">
                <div class="w-full max-w-md mx-auto">
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="text-xs font-bold uppercase tracking-[0.16em] text-[#D4A017] mb-2">Authorized personnel</div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-2 tracking-tight">Welcome back</h2>
                        <p class="text-gray-500">Sign in to your assigned monitoring workspace.</p>
                    </div>
                    
                    <!-- Success Alert -->
                    @if(session('success'))
                        <div class="alert alert-success mb-6">
                            <i class="fas fa-check-circle"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif
                    
                    <!-- Error Alert -->
                    @if($errors->any())
                        <div class="alert alert-error mb-6">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif
                    
                    <!-- Login Form -->
                    <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <!-- Email Field -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Email Address</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-lg z-10">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input 
                                    type="email" 
                                    name="email" 
                                    class="input input-bordered w-full pl-12 bg-white @error('email') input-error @enderror" 
                                    placeholder="Enter your email address"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="email"
                                >
                            </div>
                        </div>
                        
                        <!-- Password Field -->
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-semibold text-gray-700">Password</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-lg z-10">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="passwordField"
                                    class="input input-bordered w-full pl-12 pr-12 bg-white @error('password') input-error @enderror" 
                                    placeholder="Enter your password"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 z-10" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            <label class="label">
                                <span class="label-text-alt"></span>
                                <a href="#" onclick="showPasswordInfo(event)" class="label-text-alt link link-hover" style="color: #D4A017;">
                                    Forgot Password?
                                </a>
                            </label>
                        </div>
                        
                        <!-- Login Button - Icon Removed -->
                        <button type="submit" class="btn w-full text-white border-none text-base" style="background: linear-gradient(135deg, #D4A017 0%, #F4C542 100%);">
                            Sign In
                        </button>
                    </form>
                    
                    <!-- Security Notice -->
                    <div class="mt-8 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-shield-alt text-xl" style="color: #D4A017;"></i>
                            <div>
                                <h4 class="font-semibold text-gray-800 text-sm mb-1">Security Notice</h4>
                                <p class="text-xs text-gray-600">
                                    This system is for authorized personnel only. All activities are logged and monitored.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="mt-8 text-center text-sm text-gray-500">
                        <p><strong class="text-gray-700">DILG-RC System</strong> | Phase 4D</p>
                        <p class="text-xs mt-1">&copy; 2026 Department of the Interior and Local Government</p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Password Info Modal -->
    <dialog id="passwordModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i class="fas fa-info-circle" style="color: #D4A017;"></i>
                Password Information
            </h3>
            <div class="py-4 space-y-3">
                <p><strong>This is a demonstration/proposal system.</strong></p>
                <p>All test accounts use the password: <strong style="color: #D4A017;">password</strong></p>
                <p class="text-sm text-gray-600">
                    Password reset functionality will be implemented during actual deployment. For security purposes, unique passwords will be assigned to each account when the system goes live.
                </p>
            </div>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn text-white border-none" style="background: linear-gradient(135deg, #D4A017 0%, #F4C542 100%);">
                        Got it
                    </button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('passwordField');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        function showPasswordInfo(event) {
            event.preventDefault();
            document.getElementById('passwordModal').showModal();
        }
        
        // Auto-refresh CSRF token
        setInterval(function() {
            fetch('{{ route("login") }}')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newToken = doc.querySelector('input[name="_token"]')?.value;
                    if (newToken) {
                        document.querySelector('input[name="_token"]').value = newToken;
                    }
                })
                .catch(error => console.error('Token refresh failed:', error));
        }, 30 * 60 * 1000);
    </script>
</body>
</html>
