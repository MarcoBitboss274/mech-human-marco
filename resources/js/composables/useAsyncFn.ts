import { onBeforeMount, onBeforeUnmount, ref } from 'vue'

type Options<T> = {
  immediate?: boolean
  abortSilently?: boolean
  defaultData?: T | null
}

export function useAsyncFn<T extends any>(
  fn: (Params: {
    setError: (err: string) => void
    setErrorData: (err: any) => void
    signal: AbortSignal
  }) => Promise<T> | T,
  { immediate = true, abortSilently = true, defaultData = null }: Options<T> = {}
) {
  const data = ref<typeof defaultData extends null ? null : T>(defaultData as any)
  const error = ref<null | Error>(null)
  const errorData = ref<null | any>(null)
  const loading = ref(false)
  const counter = ref(0)
  let abortController: AbortController

  const setError = (err: string) => {
    error.value = new Error(err)
  }
  const setErrorData = (err: any) => {
    errorData.value = err
  }

  const execute = async () => {
    loading.value = true
    error.value = null
    errorData.value = null
    counter.value++
    abortController = new AbortController()
    try {
      data.value = await fn({ setError, setErrorData, signal: abortController.signal })
    } catch (err) {
      // If we aren't aborting silently, we want to set the error
      if (!(err instanceof Error && err.name === 'AbortError' && abortSilently)) {
        error.value = err as Error
      }
    } finally {
      loading.value = false
    }
    return data.value
  }

  if (immediate) {
    onBeforeMount(execute)
  }

  const stop = () => {
    abortController?.abort()
  }

  onBeforeUnmount(stop)

  return {
    counter,
    data,
    error,
    errorData,
    execute,
    loading,
    stop
  }
}
