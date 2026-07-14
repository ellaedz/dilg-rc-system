@extends('layouts.app')

@section('title', 'Edit Record - DILG-RC')

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

    .record-id-badge {
        display: inline-block;
        background: linear-gradient(135deg, #F4C542 0%, #D4A017 100%);
        color: #333;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
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

    .status-highlight {
        background: #fef3c7;
        padding: 0.125rem 0.5rem;
        border-radius: 0.25rem;
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
    <h1 class="page-title">✏️ Edit Record</h1>
    <p class="page-subtitle">Update record information and status</p>
</div>

<div class="form-card">
    <div class="record-id-badge">
        Record ID: {{ $record->record_id }}
    </div>

    <form action="{{ route('records.update', $record) }}" method="POST">
        @csrf
        @method('PUT')

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
                        value="{{ old('full_name', $record->full_name) }}"
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
                        value="{{ old('contact_number', $record->contact_number) }}"
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
                        value="{{ old('email', $record->email) }}"
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
                        value="{{ old('address', $record->address) }}"
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
                        <option value="Complaint" {{ old('concern_type', $record->concern_type) == 'Complaint' ? 'selected' : '' }}>Complaint</option>
                        <option value="Request" {{ old('concern_type', $record->concern_type) == 'Request' ? 'selected' : '' }}>Request</option>
                        <option value="Referral" {{ old('concern_type', $record->concern_type) == 'Referral' ? 'selected' : '' }}>Referral</option>
                        <option value="Inquiry" {{ old('concern_type', $record->concern_type) == 'Inquiry' ? 'selected' : '' }}>Inquiry</option>
                        <option value="Infrastructure Concern" {{ old('concern_type', $record->concern_type) == 'Infrastructure Concern' ? 'selected' : '' }}>Infrastructure Concern</option>
                        <option value="Governance Concern" {{ old('concern_type', $record->concern_type) == 'Governance Concern' ? 'selected' : '' }}>Governance Concern</option>
                        <option value="Public Service Concern" {{ old('concern_type', $record->concern_type) == 'Public Service Concern' ? 'selected' : '' }}>Public Service Concern</option>
                        <option value="Other" {{ old('concern_type', $record->concern_type) == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">
                        Status <span class="required">*</span> <span class="status-highlight">Update Workflow</span>
                    </label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="Pending" {{ old('status', $record->status) == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Under Review" {{ old('status', $record->status) == 'Under Review' ? 'selected' : '' }}>Under Review</option>
                        <option value="In Progress" {{ old('status', $record->status) == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="Referred" {{ old('status', $record->status) == 'Referred' ? 'selected' : '' }}>Referred</option>
                        <option value="Resolved" {{ old('status', $record->status) == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="Closed" {{ old('status', $record->status) == 'Closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                    <div class="form-help">Changing status will update the workflow timeline</div>
                </div>

                <div class="form-group full-width">
                    <label for="description" class="form-label">
                        Description <span class="required">*</span>
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea"
                        required
                    >{{ old('description', $record->description) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Assignment Section -->
        <div class="form-section">
            <h3 class="form-section-title">Assignment & Notes</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="assigned_office" class="form-label">Assigned Office</label>
                    <input 
                        type="text" 
                        id="assigned_office" 
                        name="assigned_office" 
                        class="form-input"
                        value="{{ old('assigned_office', $record->assigned_office) }}"
                    >
                </div>

                <div class="form-group">
                    <label for="assigned_personnel" class="form-label">Assigned Personnel</label>
                    <input 
                        type="text" 
                        id="assigned_personnel" 
                        name="assigned_personnel" 
                        class="form-input"
                        value="{{ old('assigned_personnel', $record->assigned_personnel) }}"
                    >
                </div>

                <div class="form-group full-width">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea 
                        id="remarks" 
                        name="remarks" 
                        class="form-textarea"
                        placeholder="Add resolution notes, updates, or additional information..."
                    >{{ old('remarks', $record->remarks) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="{{ route('records.show', $record) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update Record</button>
        </div>
    </form>
</div>
@endsection
