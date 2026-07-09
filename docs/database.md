# UniAdvisr Database Notes

This package contains a normalized MySQL schema for:
- role-based login
- student/faculty/admin profiles
- student-to-faculty assignment
- appointment booking and token queue
- early completion / queue reordering support
- document uploads
- essay submissions and reviews
- notifications
- activity logs

Import order:
1. `sql/UniAdvisr_schema.sql`
2. `sql/UniAdvisr_seed.sql`
3. Your application files
4. Connect `.env` values to the database
