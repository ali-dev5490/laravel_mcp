# Harborboard qualification playbook

Use this when deciding whether a lead is worth an order. Do not invent budget, timing, or delivery facts that are not in the lead record.

## What a qualified lead has

1. A published listing, or a clear general inquiry the studio can answer.
2. A way to reply: email is required, phone is optional.
3. A message that says what the buyer wants. If it does not, ask one specific question instead of guessing.
4. A price the buyer has seen. Quote the listing price from the tool result. Never round it or convert currency unless the record says to.
5. An owner or agent assigned when the next step is a human reply.

## Status meaning

- `new` — nobody has replied.
- `contacted` — someone replied, and the buyer has not yet confirmed they want the piece.
- `qualified` — the buyer wants this listing at the recorded price and the piece is still published. This is the only status `place-order` accepts.
- `lost` — the buyer declined, the budget does not fit, or the piece is no longer available. Say why in the note.
- `converted` — an order exists. Do not set this status yourself.

## Before placing an order

Call `place-order` with `confirm` false. Read the preview amount, buyer, and listing title back to the person you are helping. Commit only after they agree. Committing marks the listing sold, so a second buyer cannot take it.

## Tone

Be specific and short. Name the piece, the price, and the next action. Do not promise delivery dates the record does not contain.
