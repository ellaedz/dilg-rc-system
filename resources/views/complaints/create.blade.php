@extends('layouts.app')

@section('title', 'File New Complaint/Request - DILG-RC System')

@section('content')
<style>
    .form-container {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        padding: 2rem;
        max-width: 900px;
    }

    .form-intro {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .form-intro-title {
        font-weight: 600;
        color: #1e40af;
        margin-bottom: 0.5rem;
    }

    .form-intro-text {
        font-size: 0.875rem;
        color: #374151;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e5e7eb;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .form-grid-full {
        grid-column: 1 / -1;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .form-label.required:after {
        content: " *";
        color: #dc2626;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        transition: all 0.3s;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        padding: 0.875rem 2rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .btn-secondary {
        background: white;
        color: #374151;
        padding: 0.875rem 2rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .error-message {
        color: #dc2626;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .help-text {
        color: #6b7280;
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .priority-indicator {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .priority-option {
        padding: 0.75rem;
        border: 2px solid #d1d5db;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
    }

    .priority-option input[type="radio"] {
        display: none;
    }

    .priority-option:has(input:checked) {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    .priority-option:hover {
        border-color: #3b82f6;
    }

    .priority-label {
        font-weight: 600;
        font-size: 0.875rem;
    }
</style>

<div class="page-header">
    <h1 class="page-title">File New Complaint/Request</h1>
    <p class="page-subtitle">Submit your concern to DILG - Santa Cruz, Laguna</p>
</div>

<div class="form-container">
    <div class="form-intro">
        <div class="form-intro-title">📋 Filing a Complaint or Request</div>
        <div class="form-intro-text">
            Please fill out all required fields accurately. Your complaint/request will be reviewed and assigned to the appropriate office for processing.
        </div>
    </div>

    <form action="{{ route('complaints.store') }}" method="POST">
        @csrf

        <!-- Personal Information Section -->
        <div class="form-section">
            <h3 class="section-title">Personal Information</h3>
            <div class="form-grid">
                <div class="form-group form-grid-full">
                    <label for="full_name" class="form-label required">Full Name</label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           class="form-input" 
                           placeholder="Enter your full name" 
                           value="{{ old('full_name') }}"
                           required>
                    @error('full_name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="contact_number" class="form-label required">Contact Number</label>
                    <input type="text" 
                           id="contact_number" 
                           name="contact_number" 
                           class="form-input" 
                           placeholder="e.g. 09123456789" 
                           value="{{ old('contact_number') }}"
                           required>
                    @error('contact_number')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="barangay" class="form-label required">Barangay</label>
                    <select id="barangay" name="barangay" class="form-select" required>
                        <option value="">Select Barangay</option>
                        <optgroup label="Poblacion (Urban)">
                            <option value="Poblacion I" {{ old('barangay') == 'Poblacion I' ? 'selected' : '' }}>Poblacion I (Uno)</option>
                            <option value="Poblacion II" {{ old('barangay') == 'Poblacion II' ? 'selected' : '' }}>Poblacion II (Dos)</option>
                            <option value="Poblacion III" {{ old('barangay') == 'Poblacion III' ? 'selected' : '' }}>Poblacion III (Tres)</option>
                            <option value="Poblacion IV" {{ old('barangay') == 'Poblacion IV' ? 'selected' : '' }}>Poblacion IV (Cuatro)</option>
                            <option value="Poblacion V" {{ old('barangay') == 'Poblacion V' ? 'selected' : '' }}>Poblacion V (Cinco)</option>
                        </optgroup>
                        <optgroup label="Rural Barangays (A-M)">
                            <option value="Alipit" {{ old('barangay') == 'Alipit' ? 'selected' : '' }}>Alipit</option>
                            <option value="Bagumbayan" {{ old('barangay') == 'Bagumbayan' ? 'selected' : '' }}>Bagumbayan</option>
                            <option value="Bubukal" {{ old('barangay') == 'Bubukal' ? 'selected' : '' }}>Bubukal</option>
                            <option value="Calios" {{ old('barangay') == 'Calios' ? 'selected' : '' }}>Calios</option>
                            <option value="Duhat" {{ old('barangay') == 'Duhat' ? 'selected' : '' }}>Duhat</option>
                            <option value="Gatid" {{ old('barangay') == 'Gatid' ? 'selected' : '' }}>Gatid</option>
                            <option value="Jasaan" {{ old('barangay') == 'Jasaan' ? 'selected' : '' }}>Jasaan</option>
                            <option value="Labuin" {{ old('barangay') == 'Labuin' ? 'selected' : '' }}>Labuin</option>
                            <option value="Malinao" {{ old('barangay') == 'Malinao' ? 'selected' : '' }}>Malinao</option>
                        </optgroup>
                        <optgroup label="Rural Barangays (O-Z)">
                            <option value="Oogong" {{ old('barangay') == 'Oogong' ? 'selected' : '' }}>Oogong</option>
                            <option value="Pagsawitan" {{ old('barangay') == 'Pagsawitan' ? 'selected' : '' }}>Pagsawitan</option>
                            <option value="Palasan" {{ old('barangay') == 'Palasan' ? 'selected' : '' }}>Palasan</option>
                            <option value="Patimbao" {{ old('barangay') == 'Patimbao' ? 'selected' : '' }}>Patimbao</option>
                            <option value="San Jose" {{ old('barangay') == 'San Jose' ? 'selected' : '' }}>San Jose</option>
                            <option value="San Juan" {{ old('barangay') == 'San Juan' ? 'selected' : '' }}>San Juan</option>
                            <option value="San Pablo Norte" {{ old('barangay') == 'San Pablo Norte' ? 'selected' : '' }}>San Pablo Norte</option>
                            <option value="San Pablo Sur" {{ old('barangay') == 'San Pablo Sur' ? 'selected' : '' }}>San Pablo Sur</option>
                            <option value="Santisima Cruz" {{ old('barangay') == 'Santisima Cruz' ? 'selected' : '' }}>Santisima Cruz</option>
                            <option value="Santo Angel Central" {{ old('barangay') == 'Santo Angel Central' ? 'selected' : '' }}>Santo Angel Central</option>
                            <option value="Santo Angel Norte" {{ old('barangay') == 'Santo Angel Norte' ? 'selected' : '' }}>Santo Angel Norte</option>
                            <option value="Santo Angel Sur" {{ old('barangay') == 'Santo Angel Sur' ? 'selected' : '' }}>Santo Angel Sur</option>
                        </optgroup>
                    </select>
                    @error('barangay')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                    <div class="help-text">Select from 26 barangays of Santa Cruz, Laguna</div>
                </div>

                <div class="form-group form-grid-full">
                    <input type="hidden" name="municipality" value="Santa Cruz">
                    <div style="background: #eff6ff; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; color: #1e40af;">
                        📍 <strong>Municipality:</strong> Santa Cruz, Laguna
                    </div>
                </div>

                <div class="form-group form-grid-full">
                    <label for="address" class="form-label required">Complete Address</label>
                    <textarea id="address" 
                              name="address" 
                              class="form-textarea" 
                              placeholder="Street, House No., Subdivision, etc." 
                              required>{{ old('address') }}</textarea>
                    @error('address')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Complaint/Request Details Section -->
        <div class="form-section">
            <h3 class="section-title">Complaint/Request Details</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="concern_type" class="form-label required">Type of Concern</label>
                    <select id="concern_type" name="concern_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="Complaint" {{ old('concern_type') == 'Complaint' ? 'selected' : '' }}>Complaint</option>
                        <option value="Request" {{ old('concern_type') == 'Request' ? 'selected' : '' }}>Request</option>
                        <option value="Referral" {{ old('concern_type') == 'Referral' ? 'selected' : '' }}>Referral</option>
                        <option value="Inquiry" {{ old('concern_type') == 'Inquiry' ? 'selected' : '' }}>Inquiry</option>
                        <option value="Report" {{ old('concern_type') == 'Report' ? 'selected' : '' }}>Report</option>
                    </select>
                    @error('concern_type')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="subject" class="form-label required">Subject</label>
                    <input type="text" 
                           id="subject" 
                           name="subject" 
                           class="form-input" 
                           placeholder="Brief subject of your concern" 
                           value="{{ old('subject') }}"
                           required>
                    @error('subject')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group form-grid-full">
                    <label for="description" class="form-label required">Detailed Description</label>
                    <textarea id="description" 
                              name="description" 
                              class="form-textarea" 
                              style="min-height: 150px;"
                              placeholder="Provide complete details of your complaint or request"
                              required>{{ old('description') }}</textarea>
                    <div class="help-text">Include dates, locations, names, and any other relevant information</div>
                    @error('description')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Priority Section -->
        <div class="form-section">
            <h3 class="section-title">Priority Level</h3>
            <div class="form-group">
                <label class="form-label required">Select Priority</label>
                <div class="priority-indicator">
                    <label class="priority-option">
                        <input type="radio" name="priority" value="High" {{ old('priority') == 'High' ? 'checked' : '' }} required>
                        <div class="priority-label" style="color: #dc2626;">🔴 High</div>
                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">Urgent attention needed</div>
                    </label>

                    <label class="priority-option">
                        <input type="radio" name="priority" value="Medium" {{ old('priority', 'Medium') == 'Medium' ? 'checked' : '' }}>
                        <div class="priority-label" style="color: #f59e0b;">🟡 Medium</div>
                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">Normal processing</div>
                    </label>

                    <label class="priority-option">
                        <input type="radio" name="priority" value="Low" {{ old('priority') == 'Low' ? 'checked' : '' }}>
                        <div class="priority-label" style="color: #10b981;">🟢 Low</div>
                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">Non-urgent matter</div>
                    </label>
                </div>
                @error('priority')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn-primary">📤 Submit Complaint/Request</button>
            <a href="{{ route('complaints.index') }}" class="btn-secondary">❌ Cancel</a>
        </div>
    </form>
</div>
@endsection
