-- 0002_panda_waitlist_wtp.sql
-- Add the willingness-to-pay survey answer to the waitlist.
--
-- The pilot is FREE for farmers (decided 2026-10-21); the KES 200/month
-- pricing hypothesis is validated by asking at signup (and again at pilot
-- exit) whether the farmer would pay that after the pilot. 40% WTP is the
-- pricing gate (docs/pilot-recruitment-pack.md §5).
--
-- Values: yes | maybe | no | NULL (not asked / skipped).

ALTER TABLE panda_waitlist ADD COLUMN wtp TEXT
    CHECK (wtp IN ('yes', 'maybe', 'no') OR wtp IS NULL);
