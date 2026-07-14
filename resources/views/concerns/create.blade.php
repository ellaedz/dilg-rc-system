@extends('layouts.app')

@section('title', 'Manual Concern Entry - DILG-RC')

@section('content')
<style>
    .page-header {
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .page-subtitle {
        color: #6b7280;
        font-size: 0.9375rem;
    }

    .form-card {
        background: white;
        border-radius: 0.75rem;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        max-width: 900px;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .form-section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #F4C542;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .form-label .required {
        color: #ef4444;
    }

    .form-input, .form-select, .form-textarea {
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.9375rem;
        transition: all 0.3s;
        font-family: inherit;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: #F4C542;
        box-shadow: 0 0 0 3px rgba(244, 197, 66, 0.1);
    }

    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #e5e7eb;
    }

    .btn-primary {
        background: linear-gradient(135deg, #F4C542 0%, #D4A017 100%);
        color: #333;
        padding: 0.875rem 2rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 1rem;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(212, 160, 23, 0.4);
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
        padding: 0.875rem 2rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .form-help {
        font-size: 0.8125rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
</style>

<div class="page-header">
    <h1 class="page-title">📋 Manual Concern Entry</h1>
    <p class="page-subtitle">Staff backup encoding for concerns (Main submissions come from mobile app)</p>
</div>

<div class="form-card">
    <form action="{{ route('concerns.store') }}" method="POST">
        @csrf

        <!-- Personal Information Section -->
        <div class="form-section">
            <h3 class="form-section-title">Personal Information</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name" class="form-label">
                        Full Name <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-input"
                        value="{{ old('full_name') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="contact_number" class="form-label">
                        Contact Number <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="contact_number" 
                        name="contact_number" 
                        class="form-input"
                        placeholder="e.g. 09171234567"
                        value="{{ old('contact_number') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input"
                        placeholder="e.g. juan@email.com"
                        value="{{ old('email') }}"
                    >
                </div>

                <div class="form-group full-width">
                    <label for="address" class="form-label">
                        Address <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="address" 
                        name="address" 
                        class="form-input"
                        placeholder="Street, Barangay, Santa Cruz, Laguna"
                        value="{{ old('address') }}"
                        required
                    >
                </div>
            </div>
        </div>

        <!-- Concern Details Section -->
        <div class="form-section">
            <h3 class="form-section-title">Concern Details</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="concern_type" class="form-label">
                        Concern Type <span class="required">*</span>
                    </label>
                    <select id="concern_type" name="concern_type" class="form-select" required>
                        <option value="">Select Concern Type</option>
                        <option value="Complaint" {{ old('concern_type') == 'Complaint' ? 'selected' : '' }}>Complaint</option>
                        <option value="Request" {{ old('concern_type') == 'Request' ? 'selected' : '' }}>Request</option>
                        <option value="Referral" {{ old('concern_type') == 'Referral' ? 'selected' : '' }}>Referral</option>
                        <option value="Inquiry" {{ old('concern_type') == 'Inquiry' ? 'selected' : '' }}>Inquiry</option>
                        <option value="Infrastructure Concern" {{ old('concern_type') == 'Infrastructure Concern' ? 'selected' : '' }}>Infrastructure Concern</option>
                        <option value="Governance Concern" {{ old('concern_type') == 'Governance Concern' ? 'selected' : '' }}>Governance Concern</option>
                        <option value="Public Service Concern" {{ old('concern_type') == 'Public Service Concern' ? 'selected' : '' }}>Public Service Concern</option>
                        <option value="Environmental Concern" {{ old('concern_type') == 'Environmental Concern' ? 'selected' : '' }}>Environmental Concern</option>
                        <option value="Disaster / Risk Concern" {{ old('concern_type') == 'Disaster / Risk Concern' ? 'selected' : '' }}>Disaster / Risk Concern</option>
                        <option value="Road Clearing / Obstruction" {{ old('concern_type') == 'Road Clearing / Obstruction' ? 'selected' : '' }}>Road Clearing / Obstruction</option>
                        <option value="Other" {{ old('concern_type') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="description" class="form-label">
                        Description <span class="required">*</span>
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea"
                        placeholder="Please provide detailed information about your concern..."
                        required
                    >{{ old('description') }}</textarea>
                    <div class="form-help">Provide as much detail as possible to help us address your concern effectively.</div>
                </div>
            </div>
        </div>

        <!-- Assignment Section (Optional) -->
        <div class="form-section">
            <h3 class="form-section-title">Assignment (Optional)</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="assigned_office" class="form-label">Assigned Office</label>
                    <input 
                        type="text" 
                        id="assigned_office" 
                        name="assigned_office" 
                        class="form-input"
                        placeholder="e.g. Office of the Mayor"
                        value="{{ old('assigned_office') }}"
                    >
                </div>

                <div class="form-group">
                    <label for="assigned_personnel" class="form-label">Assigned Personnel</label>
                    <input 
                        type="text" 
                        id="assigned_personnel" 
                        name="assigned_personnel" 
                        class="form-input"
                        placeholder="e.g. Juan Dela Cruz"
                        value="{{ old('assigned_personnel') }}"
                    >
                </div>

                <div class="form-group full-width">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea 
                        id="remarks" 
                        name="remarks" 
                        class="form-textarea"
                        placeholder="Any additional notes or remarks..."
                    >{{ old('remarks') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="{{ route('concerns.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Submit Concern</button>
        </div>
    </form>
</div>
@endsection
