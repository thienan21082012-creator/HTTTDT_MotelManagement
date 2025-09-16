-- Add contract fields to rooms if not exists
ALTER TABLE rooms 
    ADD COLUMN IF NOT EXISTS contract_duration INT NULL,
    ADD COLUMN IF NOT EXISTS start_date DATE NULL;
