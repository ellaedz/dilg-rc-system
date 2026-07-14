@extends('layouts.app')

@section('title', 'Edit Complaint/Request - DILG-RC System')

@section('content')
<style>
    .form-container {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        padding: 2rem;
        max-width: 900px;
    }

    .complaint-id-display {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .complaint-id-text {
        font-size: 1.125rem;
        font-weight: bold;
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
</style>

<div class="page-header">
    <h1 class="page-title">Edit Complaint/Request</h1>
    <p class="page-subtitle">Update complaint information and status</p>
</div>

<div class="form-container">
    <div class="complaint-id-display">
        <span class="complaint-id-text">📝 Complaint ID: {{ $complaint->complaint_id }}</span>
        <span style="font-size: 0.875rem;">Filed: {{ $complaint->date_filed->format('M d, Y') }}</span>
    </div>

    <form action="{{ route('complaints.update', $complaint) }}" method="POST">
        @csrf
        @method('PUT')

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
                           value="{{ old('full_name', $complaint->full_name) }}"
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
                           value="{{ old('contact_number', $complaint->contact_number) }}"
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
                            <option value="Poblacion I" {{ old('barangay', $complaint->barangay) == 'Poblacion I' ? 'selected' : '' }}>Poblacion I (Uno)</option>
                            <option value="Poblacion II" {{ old('barangay', $complaint->barangay) == 'Poblacion II' ? 'selected' : '' }}>Poblacion II (Dos)</option>
                            <option value="Poblacion III" {{ old('barangay', $complaint->barangay) == 'Poblacion III' ? 'selected' : '' }}>Poblacion III (Tres)</option>
                            <option value="Poblacion IV" {{ old('barangay', $complaint->barangay) == 'Poblacion IV' ? 'selected' : '' }}>Poblacion IV (Cuatro)</option>
                            <option value="Poblacion V" {{ old('barangay', $complaint->barangay) == 'Poblacion V' ? 'selected' : '' }}>Poblacion V (Cinco)</option>
                        </optgroup>
                        <optgroup label="Rural Barangays (A-M)">
                            <option value="Alipit" {{ old('barangay', $complaint->barangay) == 'Alipit' ? 'selected' : '' }}>Alipit</option>
                            <option value="Bagumbayan" {{ old('barangay', $complaint->barangay) == 'Bagumbayan' ? 'selected' : '' }}>Bagumbayan</option>
                            <option value="Bubukal" {{ old('barangay', $complaint->barangay) == 'Bubukal' ? 'selected' : '' }}>Bubukal</option>
                            <option value="Calios" {{ old('barangay', $complaint->barangay) == 'Calios' ? 'selected' : '' }}>Calios</option>
                            <option value="Duhat" {{ old('barangay', $complaint->barangay) == 'Duhat' ? 'selected' : '' }}>Duhat</option>
                            <option value="Gatid" {{ old('barangay', $complaint->barangay) == 'Gatid' ? 'selected' : '' }}>Gatid</option>
                            <option value="Jasaan" {{ old('barangay', $complaint->barangay) == 'Jasaan' ? 'selected' : '' }}>Jasaan</option>
                            <option value="Labuin" {{ old('barangay', $complaint->barangay) == 'Labuin' ? 'selected' : '' }}>Labuin</option>
                            <option value="Malinao" {{ old('barangay', $complaint->barangay) == 'Malinao' ? 'selected' : '' }}>Malinao</option>
                        </optgroup>
                        <optgroup label="Rural Barangays (O-Z)">
                            <option value="Oogong" {{ old('barangay', $complaint->barangay) == 'Oogong' ? 'selected' : '' }}>Oogong</option>
                            <option value="Pagsawitan" {{ old('barangay', $complaint->barangay) == 'Pagsawitan' ? 'selected' : '' }}>Pagsawitan</option>
                            <option value="Palasan" {{ old('barangay', $complaint->barangay) == 'Palasan' ? 'selected' : '' }}>Palasan</option>
                            <option value="Patimbao" {{ old('barangay', $complaint->barangay) == 'Patimbao' ? 'selected' : '' }}>Patimbao</option>
                            <option value="San Jose" {{ old('barangay', $complaint->barangay) == 'San Jose' ? 'selected' : '' }}>San Jose</option>
                            <option value="San Juan" {{ old('barangay', $complaint->barangay) == 'San Juan' ? 'selected' : '' }}>San Juan</option>
                            <option value="San Pablo Norte" {{ old('barangay', $complaint->barangay) == 'San Pablo Norte' ? 'selected' : '' }}>San Pablo Norte</option>
                            <option value="San Pablo Sur" {{ old('barangay', $complaint->barangay) == 'San Pablo Sur' ? 'selected' : '' }}>San Pablo Sur</option>
                            <option value="Santisima Cruz" {{ old('barangay', $complaint->barangay) == 'Santisima Cruz' ? 'selected' : '' }}>Santisima Cruz</option>
                            <option value="Santo Angel Central" {{ old('barangay', $complaint->barangay) == 'Santo Angel Central' ? 'selected' : '' }}>Santo Angel Central</option>
                            <option value="Santo Angel Norte" {{ old('barangay', $complaint->barangay) == 'Santo Angel Norte' ? 'selected' : '' }}>Santo Angel Norte</option>
                            <option value="Santo Angel Sur" {{ old('barangay', $complaint->barangay) == 'Santo Angel Sur' ? 'selected' : '' }}>Santo Angel Sur</option>
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
                              required>{{ old('address', $complaint->address) }}</textarea>
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
                        <option value="Complaint" {{ old('concern_type', $complaint->concern_type) == 'Complaint' ? 'selected' : '' }}>Complaint</option>
                        <option value="Request" {{ old('concern_type', $complaint->concern_type) == 'Request' ? 'selected' : '' }}>Request</option>
                        <option value="Referral" {{ old('concern_type', $complaint->concern_type) == 'Referral' ? 'selected' : '' }}>Referral</option>
                        <option value="Inquiry" {{ old('concern_type', $complaint->concern_type) == 'Inquiry' ? 'selected' : '' }}>Inquiry</option>
                        <option value="Report" {{ old('concern_type', $complaint->concern_type) == 'Report' ? 'selected' : '' }}>Report</option>
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
                           value="{{ old('subject', $complaint->subject) }}"
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
                              required>{{ old('description', $complaint->description) }}</textarea>
                    @error('description')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Status & Priority Section -->
        <div class="form-section">
            <h3 class="section-title">Status & Priority</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="status" class="form-label required">Status</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="Pending" {{ old('status', $complaint->status) == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="In Progress" {{ old('status', $complaint->status) == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="Resolved" {{ old('status', $complaint->status) == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="Referred" {{ old('status', $complaint->status) == 'Referred' ? 'selected' : '' }}>Referred</option>
                        <option value="Closed" {{ old('status', $complaint->status) == 'Closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                    @error('status')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="priority" class="form-label required">Priority Level</label>
                    <select id="priority" name="priority" class="form-select" required>
                        <option value="High" {{ old('priority', $complaint->priority) == 'High' ? 'selected' : '' }}>High</option>
                        <option value="Medium" {{ old('priority', $complaint->priority) == 'Medium' ? 'selected' : '' }}>Medium</option>
                        <option value="Low" {{ old('priority', $complaint->priority) == 'Low' ? 'selected' : '' }}>Low</option>
                    </select>
                    @error('priority')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Assignment Section -->
        <div class="form-section">
            <h3 class="section-title">Assignment Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="assigned_office" class="form-label">Assigned Office</label>
                    <input type="text" 
                           id="assigned_office" 
                           name="assigned_office" 
                           class="form-input" 
                           value="{{ old('assigned_office', $complaint->assigned_office) }}">
                    @error('assigned_office')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="assigned_personnel" class="form-label">Assigned Personnel</label>
                    <input type="text" 
                           id="assigned_personnel" 
                           name="assigned_personnel" 
                           class="form-input" 
                           value="{{ old('assigned_personnel', $complaint->assigned_personnel) }}">
                    @error('assigned_personnel')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group form-grid-full">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea id="remarks" 
                              name="remarks" 
                              class="form-textarea">{{ old('remarks', $complaint->remarks) }}</textarea>
                    @error('remarks')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="btn-primary">💾 Update Complaint</button>
            <a href="{{ route('complaints.show', $complaint) }}" class="btn-secondary">❌ Cancel</a>
        </div>
    </form>
</div>
@endsection
