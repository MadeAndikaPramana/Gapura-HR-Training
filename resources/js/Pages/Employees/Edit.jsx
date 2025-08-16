// ============================================================================
// EMPLOYEE EDIT COMPONENT - REUSE CREATE COMPONENT
// ============================================================================

import React from 'react';
import EmployeeCreate from './Create';

export default function EmployeeEdit(props) {
    return (
        <EmployeeCreate
            {...props}
            title={`Edit Karyawan - ${props.employee?.nama_lengkap || 'Unknown'}`}
        />
    );
}
