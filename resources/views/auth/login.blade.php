<!DOCTYPE html>
<html lang="en" data-theme="dilg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b2c78">
    <title>Login - CIVICLEAR</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    
    <!-- Vite Assets (Tailwind CSS + DaisyUI) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-[#eaf3ff] via-[#cfe4ff] to-[#9fc8f5]">
    
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-10 relative overflow-hidden">
        <div class="absolute -top-28 -left-20 w-72 h-72 rounded-full bg-[#2f6fd1]/20 blur-sm"></div>
        <div class="absolute -bottom-40 -right-24 w-96 h-96 rounded-full bg-[#19a8d8]/20 blur-sm"></div>
        <div class="w-full max-w-6xl grid lg:grid-cols-[1.05fr_0.95fr] gap-0 bg-gradient-to-br from-[#eff7ff]/95 via-[#dcecff]/95 to-[#b8d9fb]/95 rounded-[2rem] shadow-2xl overflow-hidden border border-white/70 backdrop-blur-xl relative z-10">
            
            <!-- Left Panel - Santa Cruz blue identity -->
            <div class="bg-gradient-to-br from-[#eaf5ff] via-[#d4e9ff] to-[#b9dcfb] p-7 sm:p-10 lg:p-14 flex flex-col justify-center items-center text-center lg:text-left relative overflow-hidden">
                <!-- Decorative circles -->
                <div class="absolute -top-20 -left-20 w-60 h-60 bg-[#174ea6]/10 rounded-full"></div>
                <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-[#168fd2]/10 rounded-full"></div>
                
                <div class="relative z-10 w-full max-w-md">
                    <!-- Santa Cruz municipal mark -->
                    <div class="mb-7 flex justify-center lg:justify-start">
                        <img src="{{ asset('images/santa-cruz-logo.svg') }}" alt="Municipality of Santa Cruz, Laguna" class="w-full max-w-xs h-auto object-contain">
                    </div>
                    
                    <!-- Welcome Text -->
                    <div class="text-[#0b2c78] mb-8">
                        <h1 class="text-3xl lg:text-5xl font-bold mb-4 tracking-tight">CIVICLEAR</h1>
                        <p class="text-lg text-[#365d91]">Santa Cruz road-clearing and public-order monitoring</p>
                    </div>
                    
                    <!-- System Info Card -->
                    <div class="bg-white/45 backdrop-blur-sm rounded-2xl p-6 border border-white/70 shadow-sm">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-map-marker-alt text-[#174ea6] text-xl"></i>
                            <h3 class="text-[#0b2c78] font-semibold text-lg">Santa Cruz, Laguna</h3>
                        </div>
                        <p class="text-[#365d91] text-sm">
                            Municipality of Santa Cruz, Laguna<br>
                            Road Clearing Operations Center
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel - Login Form -->
            <div class="m-4 sm:m-6 lg:m-8 flex flex-col justify-center gap-4">
              <div class="p-7 sm:p-9 lg:p-10 flex flex-col justify-center bg-gradient-to-br from-[#f5f9ff]/95 via-[#eaf3ff]/95 to-[#dbeaff]/95 rounded-3xl shadow-xl border border-white/80">
                <div class="w-full max-w-md mx-auto">
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="text-xs font-bold uppercase tracking-[0.16em] text-[#174ea6] mb-2">Authorized personnel</div>
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

                    @if(session('error'))
                        <div class="alert alert-error mb-6" role="alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>{{ session('error') }}</span>
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
                                <a href="#" onclick="showPasswordInfo(event)" class="label-text-alt link link-hover" style="color: #174ea6;">
                                    Forgot Password?
                                </a>
                            </label>
                        </div>
                        
                        <!-- Login Button - Icon Removed -->
                        <button type="submit" class="btn w-full text-white border-none text-base" style="background: linear-gradient(135deg, #123b91 0%, #168fd2 100%);">
                            Sign In
                        </button>
                    </form>

                    <!-- Security Notice -->
                    <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-shield-alt text-xl" style="color: #174ea6;"></i>
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
                        <p><strong class="text-gray-700">CIVICLEAR</strong></p>
                        <p class="text-xs mt-1">&copy; 2026 Municipality of Santa Cruz, Laguna</p>
                    </div>
                </div>
              </div>
              <a href="{{ route('welcome') }}" class="mx-auto inline-flex items-center gap-2 px-3 py-1 text-sm font-medium text-[#174ea6] transition-colors hover:text-[#0b2c78] hover:underline focus-visible:rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#174ea6]">
                  <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
                  Back to CIVICLEAR Home
              </a>
            </div>
            
        </div>
    </div>
    
    <!-- Password Info Modal -->
    <dialog id="passwordModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg flex items-center gap-2">
                <i class="fas fa-info-circle" style="color: #174ea6;"></i>
                Password Information
            </h3>
            <div class="py-4 space-y-3">
                <p><strong>Contact your CIVICLEAR system administrator for access.</strong></p>
                <p class="text-sm text-gray-600">
                    Passwords are assigned individually and must never be shared or published.
                </p>
            </div>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn text-white border-none" style="background: linear-gradient(135deg, #123b91 0%, #168fd2 100%);">
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
