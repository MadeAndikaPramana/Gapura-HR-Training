<?php
// File: resources/views/emails/certificate-expiry-employee.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificate Expiry Reminder - GAPURA ANGKASA</title>
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #439454, #358945);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .alert {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .certificate-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
        }
        .certificate-card h3 {
            margin: 0 0 8px 0;
            color: #1f2937;
            font-size: 16px;
        }
        .expiry-date {
            color: #dc2626;
            font-weight: 600;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background: #439454;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 16px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Training Certificate Expiry Reminder</h1>
            <p>PT GAPURA ANGKASA</p>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $employee->name }}</strong>,</p>

            <div class="alert">
                <strong>⚠️ Action Required:</strong> Some of your training certificates are expiring soon and require renewal.
            </div>

            <p>The following certificates will expire within the next 30 days:</p>

            @foreach($expiringRecords as $record)
            <div class="certificate-card">
                <h3>{{ $record->trainingType->name }}</h3>
                <p><strong>Certificate Number:</strong> {{ $record->certificate_number }}</p>
                <p><strong>Expiry Date:</strong> <span class="expiry-date">{{ $record->expiry_date->format('d F Y') }}</span></p>
                <p><strong>Days Remaining:</strong> {{ \Carbon\Carbon::now()->diffInDays($record->expiry_date, false) }} days</p>
            </div>
            @endforeach

            <p><strong>What you need to do:</strong></p>
            <ul>
                <li>Contact your supervisor or HR department to schedule renewal training</li>
                <li>Ensure you complete the renewal before the expiry date</li>
                <li>Keep a copy of your new certificate for your records</li>
            </ul>

            <p>Please note that expired certificates may affect your work authorization and compliance status.</p>

            <p>If you have any questions, please contact the HR Training Department at:</p>
            <ul>
                <li>📧 Email: hr.training@gapura.com</li>
                <li>📞 Phone: +62-21-XXXX-XXXX</li>
            </ul>

            <p>Best regards,<br>
            <strong>HR Training Department</strong><br>
            PT Gapura Angkasa</p>
        </div>

        <div class="footer">
            <p>This is an automated notification from GAPURA Training Management System</p>
            <p>© {{ date('Y') }} PT Gapura Angkasa. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

<?php
// File: resources/views/emails/certificate-expiry-hr.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HR Certificate Expiry Alert - GAPURA ANGKASA</title>
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .breakdown-table th,
        .breakdown-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .breakdown-table th {
            background: #f8fafc;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Certificate Expiry Alert</h1>
            <p>HR Management Dashboard</p>
        </div>

        <div class="content" style="padding: 30px;">
            <p>Dear <strong>{{ $user->name }}</strong>,</p>

            <p>This is your regular certificate expiry notification. The following summary shows certificates expiring within {{ $summary['days_notice'] }} days:</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">{{ $summary['total_expiring'] }}</div>
                    <div class="stat-label">Total Expiring</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $summary['employees_affected'] }}</div>
                    <div class="stat-label">Employees Affected</div>
                </div>
            </div>

            <h3>📊 Breakdown by Department</h3>
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Expiring Certificates</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['by_department'] as $department => $count)
                    <tr>
                        <td>{{ $department }}</td>
                        <td>{{ $count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <h3>📋 Breakdown by Training Type</h3>
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>Training Type</th>
                        <th>Expiring Certificates</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['by_training_type'] as $type => $count)
                    <tr>
                        <td>{{ $type }}</td>
                        <td>{{ $count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <h3>🎯 Recommended Actions</h3>
            <ul>
                <li><strong>Contact Department Managers:</strong> Notify department managers about their team's expiring certificates</li>
                <li><strong>Schedule Renewal Training:</strong> Arrange renewal training sessions for affected employees</li>
                <li><strong>Update Training Calendar:</strong> Add renewal deadlines to the training calendar</li>
                <li><strong>Send Employee Notifications:</strong> Ensure all affected employees have been notified</li>
            </ul>

            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Review the detailed certificate expiry report in the training system</li>
                <li>Contact training providers to schedule renewal sessions</li>
                <li>Monitor compliance status and follow up with employees</li>
            </ol>

            <p>For detailed information, please access the Training Management System dashboard.</p>

            <p>Best regards,<br>
            <strong>Training Management System</strong><br>
            PT Gapura Angkasa</p>
        </div>

        <div style="background: #f9fafb; padding: 20px; text-align: center; color: #6b7280; font-size: 14px;">
            <p>This is an automated HR notification from GAPURA Training Management System</p>
            <p>Generated on {{ now()->format('d F Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

<?php
// File: resources/views/emails/training-assignment.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Training Certificate - GAPURA ANGKASA</title>
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #439454, #358945);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .certificate-info {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 4px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-label {
            font-weight: 600;
            color: #374151;
        }
        .info-value {
            color: #1f2937;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 New Training Certificate Issued</h1>
            <p>PT GAPURA ANGKASA</p>
        </div>

        <div style="padding: 30px;">
            <p>Dear <strong>{{ $employee->name }}</strong>,</p>

            <p>Congratulations! Your training certificate has been successfully generated and is now available.</p>

            <div class="certificate-info">
                <h3 style="margin-top: 0; color: #059669;">📜 Certificate Details</h3>

                <div class="info-row">
                    <span class="info-label">Training Type:</span>
                    <span class="info-value">{{ $trainingType->name }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Certificate Number:</span>
                    <span class="info-value">{{ $trainingRecord->certificate_number }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Issue Date:</span>
                    <span class="info-value">{{ $trainingRecord->issue_date->format('d F Y') }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Valid Until:</span>
                    <span class="info-value">{{ $trainingRecord->expiry_date->format('d F Y') }}</span>
                </div>

                @if($trainingRecord->training_provider)
                <div class="info-row">
                    <span class="info-label">Training Provider:</span>
                    <span class="info-value">{{ $trainingRecord->training_provider }}</span>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">Validity Period:</span>
                    <span class="info-value">{{ $trainingType->validity_period }} months</span>
                </div>
            </div>

            <p><strong>Important Information:</strong></p>
            <ul>
                <li>Keep this certificate information for your records</li>
                <li>You can download the official certificate from the training system</li>
                <li>Please note the expiry date and schedule renewal training accordingly</li>
                <li>This certificate is required for compliance with company training policies</li>
            </ul>

            @if($trainingType->is_mandatory)
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin: 20px 0; border-radius: 6px;">
                <strong>📋 Mandatory Training:</strong> This is a mandatory training certificate required for your position. Ensure you renew it before the expiry date.
            </div>
            @endif

            <p>If you have any questions about this certificate or need assistance, please contact:</p>
            <ul>
                <li>📧 Email: hr.training@gapura.com</li>
                <li>📞 Phone: +62-21-XXXX-XXXX</li>
            </ul>

            <p>Thank you for completing your training!</p>

            <p>Best regards,<br>
            <strong>HR Training Department</strong><br>
            PT Gapura Angkasa</p>
        </div>

        <div style="background: #f9fafb; padding: 20px; text-align: center; color: #6b7280; font-size: 14px;">
            <p>This is an automated notification from GAPURA Training Management System</p>
            <p>© {{ date('Y') }} PT Gapura Angkasa. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

<?php
// File: resources/views/emails/daily-digest.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daily Training Digest - GAPURA ANGKASA</title>
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .section {
            margin: 24px 0;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Daily Training System Digest</h1>
            <p>{{ $date }}</p>
        </div>

        <div style="padding: 30px;">
            <p>Dear <strong>{{ $user->name }}</strong>,</p>

            <p>Here's your daily summary of the GAPURA Training Management System:</p>

            <div class="section">
                <div class="section-title">📈 Key Metrics</div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">{{ $stats['total_employees'] }}</div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $stats['total_training_records'] }}</div>
                        <div class="stat-label">Training Records</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $stats['compliance_rate'] }}%</div>
                        <div class="stat-label">Compliance Rate</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $stats['certificates_issued_today'] }}</div>
                        <div class="stat-label">Issued Today</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">⚠️ Expiry Alerts</div>
                <ul>
                    <li><strong>Today:</strong> {{ $stats['expiring_today'] }} certificates expire</li>
                    <li><strong>This Week:</strong> {{ $stats['expiring_this_week'] }} certificates expire</li>
                    <li><strong>This Month:</strong> {{ $stats['expiring_this_month'] }} certificates expire</li>
                    <li><strong>Total Expired:</strong> {{ $stats['expired_total'] }} certificates</li>
                </ul>
            </div>

            <div class="section">
                <div class="section-title">👥 Department Statistics</div>
                @foreach($stats['department_stats'] as $dept)
                <div style="margin: 8px 0; padding: 8px; background: #f8fafc; border-radius: 4px;">
                    <strong>{{ $dept['department'] }}:</strong> {{ $dept['total_employees'] }} employees,
                    {{ $dept['total_valid_trainings'] }} valid trainings
                    ({{ $dept['avg_trainings_per_employee'] }} avg per employee)
                </div>
                @endforeach
            </div>

            <div class="section">
                <div class="section-title">📚 Training Type Performance</div>
                @foreach($stats['training_type_stats'] as $type)
                <div style="margin: 8px 0; padding: 8px; background: #f8fafc; border-radius: 4px;">
                    <strong>{{ $type['name'] }}</strong>
                    @if($type['is_mandatory'])<span style="color: #dc2626;">(Mandatory)</span>@endif
                    <br>
                    <small>{{ $type['valid_records'] }}/{{ $type['total_records'] }} valid ({{ $type['compliance_rate'] }}% compliance)</small>
                </div>
                @endforeach
            </div>

            <div class="section">
                <div class="section-title">📝 Today's Activity</div>
                <ul>
                    <li>New employees added: {{ $stats['new_employees_today'] }}</li>
                    <li>Certificates issued: {{ $stats['certificates_issued_today'] }}</li>
                    <li>Certificates expiring today: {{ $stats['expiring_today'] }}</li>
                </ul>
            </div>

            <p>For detailed reports and management actions, please access the Training Management System dashboard.</p>

            <p>Best regards,<br>
            <strong>Training Management System</strong><br>
            PT Gapura Angkasa</p>
        </div>

        <div style="background: #f9fafb; padding: 20px; text-align: center; color: #6b7280; font-size: 14px;">
            <p>This is an automated daily digest from GAPURA Training Management System</p>
            <p>Generated on {{ now()->format('d F Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

<?php
// File: resources/views/emails/compliance-reminder.blade.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Training Compliance Reminder - GAPURA ANGKASA</title>
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .training-card {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
        }
        .training-card h3 {
            margin: 0 0 8px 0;
            color: #991b1b;
        }
        .urgent-alert {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 16px;
            margin: 20px 0;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Training Compliance Reminder</h1>
            <p>Action Required</p>
        </div>

        <div style="padding: 30px;">
            <p>Dear <strong>{{ $employee->name }}</strong>,</p>

            <div class="urgent-alert">
                <strong>🚨 URGENT:</strong> You are currently non-compliant with mandatory training requirements. Immediate action is required to maintain your work authorization.
            </div>

            <p>Our records show that you are missing the following mandatory training certifications:</p>

            @foreach($nonCompliantTrainings as $training)
            <div class="training-card">
                <h3>{{ $training->name }}</h3>
                <p><strong>Category:</strong> {{ $training->category }}</p>
                <p><strong>Validity Period:</strong> {{ $training->validity_period }} months</p>
                @if($training->description)
                <p><strong>Description:</strong> {{ $training->description }}</p>
                @endif
            </div>
            @endforeach

            <p><strong>Immediate Actions Required:</strong></p>
            <ol>
                <li><strong>Contact your supervisor immediately</strong> to discuss training schedule</li>
                <li><strong>Schedule required training sessions</strong> with the HR Training Department</li>
                <li><strong>Complete all mandatory training</strong> as soon as possible</li>
                <li><strong>Obtain valid certificates</strong> for all required training types</li>
            </ol>

            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin: 20px 0; border-radius: 6px;">
                <strong>⏰ Important Notice:</strong> Failure to complete mandatory training may result in work restrictions or suspension until compliance is achieved.
            </div>

            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>Reply to this email acknowledging receipt</li>
                <li>Provide a plan and timeline for completing the required training</li>
                <li>Keep HR updated on your training progress</li>
            </ul>

            <p>For immediate assistance or to schedule training, contact:</p>
            <ul>
                <li>📧 Email: hr.training@gapura.com</li>
                <li>📞 Phone: +62-21-XXXX-XXXX (Extension: Training)</li>
                <li>🏢 Visit: HR Training Department, Office Hours: 08:00-17:00</li>
            </ul>

            <p>We are here to help you achieve compliance quickly and efficiently.</p>

            <p>Best regards,<br>
            <strong>HR Training Department</strong><br>
            PT Gapura Angkasa</p>
        </div>

        <div style="background: #f9fafb; padding: 20px; text-align: center; color: #6b7280; font-size: 14px;">
            <p>This is an automated compliance notification from GAPURA Training Management System</p>
            <p>© {{ date('Y') }} PT Gapura Angkasa. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
