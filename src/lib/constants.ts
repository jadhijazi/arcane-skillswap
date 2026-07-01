// Display-only. The backend computes the actual booking amount/commission
// server-side (see BookingService / WalletService); this constant exists so
// the UI can show "platform fee (10%)" copy without a dedicated config
// endpoint. Update this if the backend's commission rate changes.
export const COMMISSION_RATE = 0.1
