#!/usr/bin/env bash
set -euo pipefail

BOLD='\033[1m'; CYAN='\033[0;36m'; GREEN='\033[0;32m'
YELLOW='\033[1;33m'; RESET='\033[0m'

divider() { echo -e "${CYAN}══════════════════════════════════════════════${RESET}"; }
header()  { divider; echo -e "${BOLD}$1${RESET}"; divider; }

tracked_count() {
    local pattern="$1"
    (git ls-files | grep -E "$pattern" || true) | wc -l | tr -d '[:space:]'
}

tracked_loc() {
    local pattern="$1"
    local output

    output=$(
        (git ls-files -z | grep -zE "$pattern" || true) \
            | xargs -0 -r wc -l 2>/dev/null \
            | awk '$NF != "total" {sum += $1} END {print sum + 0}'
    )

    echo "  ${output} total"
}

tracked_declaration_count() {
    local pattern="$1"

    (
        (git ls-files -z | grep -zE '^app/.*\.php$' || true) \
            | xargs -0 -r grep -hE "$pattern" 2>/dev/null \
            || true
    ) | wc -l | tr -d '[:space:]'
}

tracked_occurrence_count() {
    local pattern="$1"

    (
        (git ls-files -z | grep -zE '^app/.*\.php$' || true) \
            | xargs -0 -r grep -hoE "$pattern" 2>/dev/null \
            || true
    ) | wc -l | tr -d '[:space:]'
}

header "1. TRACKED FILE & DIRECTORY COUNT"
tracked_files=$(git ls-files | wc -l | tr -d '[:space:]')
tracked_dirs=$(
    git ls-files | awk -F/ '
        {
            path = ""
            for (i = 1; i < NF; i++) {
                path = (path == "" ? $i : path "/" $i)
                seen[path] = 1
            }
        }
        END {
            count = 0
            for (dir in seen) count++
            print count
        }
    '
)

echo "Tracked files : $tracked_files"
echo "Tracked dirs  : $tracked_dirs"
echo "PHP app files : $(tracked_count '^app/.*\.php$')"
echo "PHP DB files  : $(tracked_count '^database/.*\.php$')"
echo "Test files    : $(tracked_count '^tests/.*\.php$')"
echo "Blade files   : $(tracked_count '^resources/.*\.blade\.php$')"
echo "Markdown docs : $(tracked_count '^docs/.*\.md$')"
echo "Migrations    : $(tracked_count '^database/migrations/.*\.php$')"
echo "Route files   : $(tracked_count '^routes/.*\.php$')"

header "2. TRACKED LINES OF CODE (LOC)"
echo -e "${YELLOW}PHP (app/):${RESET}"
tracked_loc '^app/.*\.php$'
echo -e "${YELLOW}PHP (tests/):${RESET}"
tracked_loc '^tests/.*\.php$'
echo -e "${YELLOW}PHP (database/):${RESET}"
tracked_loc '^database/.*\.php$'
echo -e "${YELLOW}Blade (resources/):${RESET}"
tracked_loc '^resources/.*\.blade\.php$'
echo -e "${YELLOW}Markdown (docs/):${RESET}"
tracked_loc '^docs/.*\.md$'

header "3. GIT COMMIT OVERVIEW"
TOTAL_COMMITS=$(git rev-list --count HEAD 2>/dev/null || echo 0)
ROOT_SHA=$(git rev-list --max-parents=0 HEAD 2>/dev/null | tail -1 || true)
if [ -n "$ROOT_SHA" ]; then
    FIRST_COMMIT=$(git show -s --format='%h %s' "$ROOT_SHA")
else
    FIRST_COMMIT="None"
fi
LAST_COMMIT=$(git log -1 --oneline HEAD 2>/dev/null || echo "None")
echo "Total commits  : $TOTAL_COMMITS"
echo "First commit   : $FIRST_COMMIT"
echo "Last commit    : $LAST_COMMIT"

header "4. GIT COMMIT SUMMARY (Monthly)"
if git rev-parse --git-dir > /dev/null 2>&1; then
    git log --format='%ad' --date=format:'%Y-%m' | sort | uniq -c | sort -k2
else
    echo "Bukan repositori git."
fi

header "5. COMMIT FREQUENCY (days with commits)"
if git rev-parse --git-dir > /dev/null 2>&1; then
    unique_days=$(git log --format='%ad' --date=format:'%Y-%m-%d' | sort -u | wc -l | tr -d '[:space:]')
    echo "Unique days with commits: $unique_days"
    echo ""
    echo -e "${YELLOW}Commits per weekday:${RESET}"
    git log --format='%ad' --date=format:'%A' | sort | uniq -c | sort -rn
else
    echo "Bukan repositori git."
fi

header "6. TOP 20 MOST CHANGED PHP FILES (historical churn)"
if git rev-parse --git-dir > /dev/null 2>&1; then
    git log --name-only --format='' \
        | grep '\.php$' \
        | sort \
        | uniq -c \
        | sort -rn \
        | head -20 \
        || true
else
    echo "Bukan repositori git."
fi

header "7. TOP 15 BIGGEST TRACKED PHP FILES (LOC)"
(
    git ls-files -z \
        | grep -zE '^(app|tests)/.*\.php$' \
        | xargs -0 -r wc -l 2>/dev/null \
        | grep -vE '[[:space:]]total$' \
        | sort -rn \
        | head -15
) || true

header "8. MIGRATION TIMELINE"
migration_files=$(git ls-files | grep -E '^database/migrations/.*\.php$' || true)
if [ -n "$migration_files" ]; then
    printf '%s\n' "$migration_files" \
        | sed 's|database/migrations/||' \
        | awk -F'_' '{print $1"-"$2"-"$3}' \
        | sort \
        | uniq -c
else
    echo "Tidak ada file migrasi tracked."
fi

header "9. TRACKED TEST COUNT PER DOMAIN"
git ls-files | awk -F/ '
    $1 == "tests" && $2 == "Feature" && NF >= 4 && $NF ~ /\.php$/ {
        count[$3]++
    }
    END {
        for (domain in count) {
            printf "  %s: %d\n", domain, count[domain]
        }
    }
' | sort -t: -k2 -rn

header "10. PORT / ADAPTER / CORE RATIO"
ports=$(tracked_count '^app/Ports/.*\.php$')
adapters_in=$(tracked_count '^app/Adapters/In/.*\.php$')
adapters_out=$(tracked_count '^app/Adapters/Out/.*\.php$')
core=$(tracked_count '^app/Core/.*\.php$')
application=$(tracked_count '^app/Application/.*\.php$')
t_files=$(tracked_count '^tests/.*\.php$')
a_files=$(tracked_count '^app/.*\.php$')
echo "  Ports        : $ports"
echo "  Adapters/In  : $adapters_in"
echo "  Adapters/Out : $adapters_out"
echo "  Core         : $core"
echo "  Application  : $application"
echo "  Ratio test:src = $t_files:$a_files"

header "11. STRICT_TYPES COVERAGE"
total_php=$(tracked_count '^app/.*\.php$')
if [ "$total_php" -gt 0 ]; then
    strict=$(
        (
            (git ls-files -z | grep -zE '^app/.*\.php$' || true) \
                | xargs -0 -r grep -l 'declare(strict_types=1)' 2>/dev/null \
                || true
        ) | wc -l | tr -d '[:space:]'
    )
    pct=$(awk -v strict="$strict" -v total="$total_php" 'BEGIN { printf "%.1f", strict * 100 / total }')
    echo "  Files with strict_types : $strict / $total_php (${pct}%)"
else
    echo "  Tidak ada file PHP tracked di folder app/."
fi

header "12. CLASS / INTERFACE DECLARATIONS"
final=$(tracked_declaration_count '^[[:space:]]*final([[:space:]]+readonly)?[[:space:]]+class[[:space:]]')
abstract=$(tracked_declaration_count '^[[:space:]]*abstract[[:space:]]+class[[:space:]]')
open=$(tracked_declaration_count '^[[:space:]]*(readonly[[:space:]]+)?class[[:space:]]')
iface=$(tracked_declaration_count '^[[:space:]]*interface[[:space:]]')
echo "  final class    : $final"
echo "  abstract class : $abstract"
echo "  open class     : $open"
echo "  interface      : $iface"

header "13. READONLY / IMMUTABILITY SIGNALS"
readonly_count=$(tracked_occurrence_count '(private|public|protected)[[:space:]]+readonly')
immutable_dt=$(tracked_occurrence_count 'DateTimeImmutable')
echo "  readonly property signals : $readonly_count occurrences"
echo "  DateTimeImmutable uses    : $immutable_dt occurrences"

divider
echo -e "${GREEN}${BOLD}  REPORT SELESAI.${RESET}"
divider
