from djitellopy import Tello

# Create Tello object
tello = Tello()

print("Connecting to Tello...")
tello.connect()

# Get and display battery level
battery = tello.get_battery()
print(f" Connection successful! Battery level: {battery}%")

# Disconnect cleanly
tello.end()
