"""UI-only guard: native form attributes and PHP entry gate stay unchanged."""
from pathlib import Path
import re
import subprocess

root = Path(__file__).resolve().parents[1]
templates = [
    "pm", "pm-users-v2", "pm-projects", "pm-timesheets", "pm-audit",
    "pm-costs", "pm-allocations", "pm-reports", "pm-imports",
    "pm-notifications", "pm-login", "pm-project-form", "pm-task-form",
    "pm-rates-panel",
]


def native_contract(source):
    tags = re.findall(r"<(?:form|input|select|textarea|button)\b[^>]*>", source)
    return [re.sub(r'\sclass="[^"]*"', "", tag) for tag in tags]


for template in templates:
    name = f"templates/admin/{template}.tpl.html"
    previous = subprocess.check_output(
        ["git", "-c", f"safe.directory={root.as_posix()}", "show", f"26d9573:{name}"],
        cwd=root,
    ).decode("utf-8")
    current = (root / name).read_text(encoding="utf-8")
    assert native_contract(current) == native_contract(previous), name

previous = subprocess.check_output(
    ["git", "-c", f"safe.directory={root.as_posix()}", "show", "26d9573:pm-launch.php"],
    cwd=root,
).decode("utf-8")
assert previous.split("?>", 1)[0] == (root / "pm-launch.php").read_text(encoding="utf-8").split("?>", 1)[0]
print("PASS: 14 templates preserve native form contracts; public entry PHP gate unchanged. No database.")
