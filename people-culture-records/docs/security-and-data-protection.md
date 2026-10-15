# HRMS security and data protection baseline

The HRMS processes employee and dependant personal data. Sensitive fields including national identifiers, birth dates, private notes, termination comments, and sensitive funeral-claim narrative fields use application-level encryption. Passwords are one-way hashed.

Run this before every production deployment:

```powershell
php artisan security:production-check
```

The command must pass. Production HTTP requests are blocked with a generic 503 response while critical security settings remain unsafe.

## Required production controls

- HTTPS for every browser and system-to-system connection.
- `APP_DEBUG=false`, encrypted secure sessions, and secure HTTP-only cookies.
- A production database rather than SQLite.
- Encrypted database/document disks and encrypted, access-controlled backups.
- Server and sensitive-data storage in Zambia unless legally authorised otherwise.
- A protected and separately backed-up `APP_KEY`; changing or losing it makes encrypted records unreadable.
- Individual least-privilege accounts, access reviews, audit monitoring, patching, and tested restoration.
- Approved privacy notice, lawful basis, retention schedule, data-subject request process, breach response, Record of Processing Activities, and DPIA.
