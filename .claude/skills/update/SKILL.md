---
name: update
description: Post an update announcement to the Myrefell Discord #updates channel
---

# Post Update to Discord

Post an update announcement to the Myrefell Discord #updates channel.

## Instructions

When the user invokes `/update`, you should:

1. Check for changes to summarize:
   - Run `git diff --stat` to see uncommitted changes
   - If no uncommitted changes, run `git log -1 --pretty=format:"%s%n%n%b" && git diff HEAD~1 --stat` to get the last commit
2. Summarize the changes in 1-2 short paragraphs that are player-friendly (not technical)
3. Focus on what changed from the player's perspective and why it matters to them
4. Post to Discord using the webhook below

If the user provides a description like `/update <description>`, use that as context for what to highlight.

## Discord Webhook

```
https://discord.com/api/webhooks/1469088961925480551/rH_mNGLf_bDdsMqpgKDv4sIai-YbaCtzNsnbsIa0-vYGf3h9Itp-2MXOMr-AaCTD5_iI
```

## Post Format

Use a Discord embed with:
- **title**: Short, catchy summary of the update (under 60 chars)
- **description**: 1-2 paragraphs explaining the change in player-friendly terms
- **color**: 5814783 (Myrefell green)
- **footer**: "Myrefell Update"

## Example curl command

```bash
curl -X POST "https://discord.com/api/webhooks/1469088961925480551/rH_mNGLf_bDdsMqpgKDv4sIai-YbaCtzNsnbsIa0-vYGf3h9Itp-2MXOMr-AaCTD5_iI" \
  -H "Content-Type: application/json" \
  -d '{
    "embeds": [{
      "title": "Update Title Here",
      "description": "Description of the update here. Keep it friendly and focused on player impact.",
      "color": 5814783,
      "footer": {
        "text": "Myrefell Update"
      }
    }]
  }'
```

## Tips

- Write for players, not developers
- Explain *why* the change matters, not just *what* changed
- Keep it concise - 1-2 paragraphs max
- Use **bold** for emphasis on key points
- Escape single quotes in the JSON with `'\''`
