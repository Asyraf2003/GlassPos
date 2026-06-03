# 0016 - Cashier Stepper Mobile UI Redesign Handoff

## Metadata

- Date: 2026-06-03
- Topic: Cashier UI redesign
- Selected direction: Variant 02 - Stepper Nota Mobile
- Status: Ready for next-session inventory
- Implementation status: Not started

## FACT

The selected UI direction is Variant 02 - Stepper Nota Mobile.

The target is not only create transaction.

The target is cashier-wide UI consistency, including:

- dashboard
- create transaction
- edit transaction
- cashier note list/history
- cashier note detail
- cashier-visible payment/refund actions

The UI should become mobile-first and stepper/card based.

The UI lab was used only to choose direction.

The production implementation has not started in this handoff.

## GAP

- Actual local cashier dashboard file paths are not proven in this handoff.
- Actual local cashier list/detail view paths are not proven in this handoff.
- Current line counts after UI lab changes are not summarized here.
- Browser/mobile review proof for production pages does not exist yet.
- No production create/edit UI patch has been made under this selected direction.
- No tests have been run for the final production redesign because it has not started.

## DECISION

Use `docs/03_blueprints/ui/0011_cashier_stepper_mobile_ui_redesign.md` as the source blueprint for the next session.

Do not use the 10-variant UI lab as the final target.

Do not implement all cashier pages in one patch.

Do not change backend behavior as part of UI redesign unless a later blueprint explicitly allows it.

## Required Reading Next Session

Read repo rules first:

- docs/04_lifecycle/handoff/README.md
- docs/01_standards/0005_handoff_template.md
- docs/01_standards/core/0010_scope_and_facts.md
- docs/01_standards/core/0011_blueprint_first.md
- docs/01_standards/core/0012_step_by_step_execution.md
- docs/01_standards/core/0013_proof_and_progress.md
- docs/01_standards/workflow/0020_response_structure.md
- docs/01_standards/workflow/0021_active_step_policy.md
- docs/01_standards/output/0033_terminal_command_delivery.md

Then read:

- docs/03_blueprints/ui/0011_cashier_stepper_mobile_ui_redesign.md
- docs/04_lifecycle/handoff/0016_cashier_stepper_mobile_ui_redesign_handoff.md

## Next Active Step

Inventory cashier UI files and produce implementation map.

No production patch before inventory.

## Suggested Inventory Commands

Run from repo root:

    printf '\n===== CASHIER ROUTES =====\n'
    rg -n "cashier|workspace|dashboard|notes" routes

    printf '\n===== CASHIER CONTROLLERS =====\n'
    find app/Adapters/In/Http/Controllers -path '*Cashier*' -type f | sort

    printf '\n===== CASHIER VIEWS =====\n'
    find resources/views -path '*cashier*' -type f | sort

    printf '\n===== CASHIER WORKSPACE JS =====\n'
    find public/assets/static/js/pages -path '*cashier*' -type f | sort

    printf '\n===== CURRENT MOBILE UI LAB FILES =====\n'
    find resources/views/cashier/notes/workspace/mobile-ui-lab -type f | sort

    printf '\n===== LINE COUNTS CASHIER VIEWS =====\n'
    find resources/views -path '*cashier*' -type f -name '*.blade.php' -print0 | sort -z | xargs -0 wc -l

## Implementation Map Expected Output

The next session should produce a map with:

- current file path
- current purpose
- whether it is create/edit/dashboard/list/detail
- whether mobile redesign applies
- expected new component/partial
- risk level
- proposed patch order
- verification command

## Recommended Patch Order After Inventory

1. Shared cashier mobile stepper/card components
2. Create transaction UI
3. Edit transaction UI
4. Dashboard
5. List/history
6. Detail/actions
7. cleanup UI lab if no longer needed

## Opening Prompt For Next Session

START PROMPT

Kita lanjut project HyperPOS di repo lokal Laravel.

Baca rules dulu sebelum jawab atau patch:

docs/04_lifecycle/handoff/README.md
docs/01_standards/0005_handoff_template.md
docs/01_standards/core/0010_scope_and_facts.md
docs/01_standards/core/0011_blueprint_first.md
docs/01_standards/core/0012_step_by_step_execution.md
docs/01_standards/core/0013_proof_and_progress.md
docs/01_standards/workflow/0020_response_structure.md
docs/01_standards/workflow/0021_active_step_policy.md
docs/01_standards/output/0033_terminal_command_delivery.md

Baca blueprint dan handoff aktif:

docs/03_blueprints/ui/0011_cashier_stepper_mobile_ui_redesign.md
docs/04_lifecycle/handoff/0016_cashier_stepper_mobile_ui_redesign_handoff.md

Keputusan UI:
- Variant 02 - Stepper Nota Mobile dipilih sebagai arah final.
- Target redesign adalah seluruh area kasir: dashboard, create transaction, edit transaction, list/history, detail, dan action kasir.
- Jangan pakai 10 variant UI lab sebagai target final.
- UI harus mobile-first, stepper/card based, dan konsisten.
- Backend behavior tidak boleh diubah tanpa blueprint baru.

Cara kerja wajib:
- Rules repo harus dibaca dulu.
- Local command output adalah source of truth.
- Jangan mengarang file, status repo, hasil test, atau hasil command.
- Gunakan struktur FACT / GAP / DECISION / ACTIVE STEP / PROOF / NEXT untuk kerja teknis.
- Blueprint-first sebelum implementasi.
- Satu active step per respons.
- Jangan patch produksi sebelum inventory cashier UI dibuat.

Active step pertama:
Inventory cashier UI files dan buat implementation map.

Gunakan command inventory dari handoff, lalu laporkan hasilnya sebelum patch.

END PROMPT
