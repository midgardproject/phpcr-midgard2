from gi.repository import Midgard

# Initialize types in GType system */
Midgard.init()

# Read configuration file
config = Midgard.Config()
config.read_file_at_path("/tmp/JackalopeMidgard2/midgard2.conf")

# Establish connection 
mgd = Midgard.Connection()
mgd.open_config(config)

# Get object by specified path
nt_folder = Midgard.SchemaObjectFactory.get_object_by_path(mgd, 'nt_folder', '/jackalope/tests_general_base')

# Read jcr:primaryType
type = nt_folder.get_property('jcr-primaryType')
print (type)

ref = Midgard.ReflectorProperty(dbclass = "nt_folder");
print(ref.get_user_value('jcr-created', 'isAutoCreated'))

Midgard.close()

