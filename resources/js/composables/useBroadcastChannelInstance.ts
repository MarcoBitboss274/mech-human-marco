import { useBroadcastChannelInstance } from 'bitboss-ui'

export const { useBroadCastChannel } = useBroadcastChannelInstance<{
  'user:profile': never
  'user:logout': never
  'user:email-verified': never
}>()
